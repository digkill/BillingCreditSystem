package messaging

import (
	"context"
	"encoding/json"
	"fmt"
	"time"

	amqp "github.com/rabbitmq/amqp091-go"
	"github.com/rs/zerolog"
)

type LoanApplicationSubmitted struct {
	LoanID     string  `json:"loan_id"`
	CustomerID string  `json:"customer_id"`
	Amount     int64   `json:"principal_amount"`
	Currency   string  `json:"currency"`
	Interest   float64 `json:"interest_rate"`
	TermMonths int     `json:"term_months"`
}

type LoanApplicationHandler interface {
	OnSubmitted(ctx context.Context, event LoanApplicationSubmitted) error
}

type Consumer struct {
	url     string
	logger  zerolog.Logger
	handler LoanApplicationHandler
}

func NewConsumer(url string, log zerolog.Logger, handler LoanApplicationHandler) *Consumer {
	return &Consumer{url: url, logger: log, handler: handler}
}

func (c *Consumer) Run(ctx context.Context) error {
	conn, err := amqp.Dial(c.url)
	if err != nil {
		return fmt.Errorf("connect to rabbitmq: %w", err)
	}
	defer conn.Close()

	ch, err := conn.Channel()
	if err != nil {
		return fmt.Errorf("open channel: %w", err)
	}
	defer ch.Close()

	msgs, err := ch.ConsumeWithContext(ctx, "loan_applications", "risk-engine", true, false, false, false, nil)
	if err != nil {
		return fmt.Errorf("consume queue: %w", err)
	}

	for {
		select {
		case <-ctx.Done():
			return nil
		case msg, ok := <-msgs:
			if !ok {
				return nil
			}

			var event LoanApplicationSubmitted
			if err := json.Unmarshal(msg.Body, &event); err != nil {
				c.logger.Error().Err(err).Msg("invalid loan event payload")
				continue
			}

			if err := c.handler.OnSubmitted(ctx, event); err != nil {
				c.logger.Error().Err(err).Msg("handle loan submitted")
			}
		}
	}
}

func (c *Consumer) StartAsync(ctx context.Context, errCh chan<- error) {
	go func() {
		errCh <- c.Run(ctx)
	}()
}

func (c *Consumer) Health() map[string]any {
	return map[string]any{
		"connected":  true,
		"checked_at": time.Now().UTC(),
	}
}
