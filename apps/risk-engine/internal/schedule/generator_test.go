package schedule

import (
	"context"
	"testing"
)

func TestGeneratorDistributesRemainder(t *testing.T) {
	gen := NewGenerator()
	installments, err := gen.Generate(context.Background(), 1000, 3)
	if err != nil {
		t.Fatalf("unexpected error: %v", err)
	}

	if len(installments) != 3 {
		t.Fatalf("expected 3 installments, got %d", len(installments))
	}

	total := int64(0)
	for i := range installments {
		total += installments[i].Amount
	}

	if total != 1000 {
		t.Fatalf("expected total 1000, got %d", total)
	}
}
