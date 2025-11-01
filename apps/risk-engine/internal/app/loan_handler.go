package app

import (
	"context"
	"encoding/json"
	"fmt"

	"github.com/rs/zerolog"

	"github.com/digkill/billing-credit-system/risk-engine/internal/messaging"
	"github.com/digkill/billing-credit-system/risk-engine/internal/schedule"
)

type loanHandler struct {
	scheduler *schedule.Generator
	log       zerolog.Logger
}

func newLoanHandler(scheduler *schedule.Generator, log zerolog.Logger) messaging.LoanApplicationHandler {
	return &loanHandler{scheduler: scheduler, log: log}
}

func (h *loanHandler) OnSubmitted(ctx context.Context, event messaging.LoanApplicationSubmitted) error {
	installments, err := h.scheduler.Generate(ctx, event.Amount, event.TermMonths)
	if err != nil {
		return fmt.Errorf("generate schedule: %w", err)
	}

	payload, _ := json.Marshal(struct {
		LoanID       string      `json:"loan_id"`
		Installments interface{} `json:"installments"`
	}{LoanID: event.LoanID, Installments: installments})

	h.log.Info().RawJSON("schedule", payload).Msg("generated schedule")

	return nil
}
