package main

import (
	"context"
	"log"
	"os/signal"
	"syscall"

	"github.com/digkill/billing-credit-system/risk-engine/internal/app"
)

func main() {
	ctx, stop := signal.NotifyContext(context.Background(), syscall.SIGINT, syscall.SIGTERM)
	defer stop()

	application, err := app.New()
	if err != nil {
		log.Fatalf("initialise app: %v", err)
	}

	if err := application.Run(ctx); err != nil {
		log.Fatalf("risk-engine stopped: %v", err)
	}
}
