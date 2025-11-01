package score

import "context"

// Service encapsulates risk scoring logic.
type Service struct{}

func NewService() *Service {
	return &Service{}
}

// EvaluateRisk currently returns a static score; integrate ML or heuristics later.
func (s *Service) EvaluateRisk(ctx context.Context, payload map[string]any) (float64, error) {
	return 0.5, nil
}
