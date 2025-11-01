package app

import (
	"context"
	"fmt"
	"sync"

	"github.com/digkill/billing-credit-system/risk-engine/internal/config"
	httpserver "github.com/digkill/billing-credit-system/risk-engine/internal/http"
	"github.com/digkill/billing-credit-system/risk-engine/internal/logger"
	"github.com/digkill/billing-credit-system/risk-engine/internal/messaging"
	"github.com/digkill/billing-credit-system/risk-engine/internal/schedule"
	"github.com/digkill/billing-credit-system/risk-engine/internal/score"
)

type App struct {
	cfg      config.Config
	http     *httpserver.Server
	consumer *messaging.Consumer
	shutdown []func(context.Context) error
}

func New() (*App, error) {
	cfg, err := config.Load()
	if err != nil {
		return nil, fmt.Errorf("load config: %w", err)
	}

	log := logger.New(cfg.LogLevel)
	scoreSvc := score.NewService()
	scheduler := schedule.NewGenerator()

	httpSrv := httpserver.New(cfg, log, scoreSvc, scheduler)
	loanHandler := newLoanHandler(scheduler, log)
	consumer := messaging.NewConsumer(cfg.RabbitURL, log, loanHandler)

	app := &App{
		cfg:      cfg,
		http:     httpSrv,
		consumer: consumer,
		shutdown: []func(context.Context) error{
			httpSrv.Shutdown,
		},
	}

	return app, nil
}

func (a *App) Run(ctx context.Context) error {
	ctx, cancel := context.WithCancel(ctx)
	defer cancel()

	var wg sync.WaitGroup
	errCh := make(chan error, 2)

	wg.Add(1)
	go func() {
		defer wg.Done()
		errCh <- a.http.Start()
	}()

	wg.Add(1)
	go func() {
		defer wg.Done()
		errCh <- a.consumer.Run(ctx)
	}()

	var runErr error
	select {
	case <-ctx.Done():
		runErr = ctx.Err()
	case err := <-errCh:
		runErr = err
	}

	shutdownCtx, cancelShutdown := context.WithTimeout(context.Background(), a.cfg.ShutdownTimeout)
	defer cancelShutdown()

	for _, hook := range a.shutdown {
		if err := hook(shutdownCtx); err != nil {
			runErr = err
		}
	}

	cancel()
	wg.Wait()

	return runErr
}
