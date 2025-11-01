package schedule

import (
	"context"
	"fmt"
	"time"
)

type Installment struct {
	DueDate time.Time
	Amount  int64
}

type Generator struct{}

func NewGenerator() *Generator {
	return &Generator{}
}

func (g *Generator) Generate(ctx context.Context, principal int64, termMonths int) ([]Installment, error) {
	if termMonths <= 0 {
		return nil, ErrInvalidTerm
	}

	base := principal / int64(termMonths)
	remainder := principal % int64(termMonths)

	installments := make([]Installment, termMonths)
	start := time.Now().AddDate(0, 1, 0)

	for i := 0; i < termMonths; i++ {
		amount := base
		if int64(i) < remainder {
			amount++
		}

		installments[i] = Installment{
			DueDate: start.AddDate(0, i, 0),
			Amount:  amount,
		}
	}

	return installments, nil
}

var ErrInvalidTerm = fmt.Errorf("term must be positive")
