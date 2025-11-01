package httpserver

import (
	"context"
	"encoding/json"
	"fmt"
	stdhttp "net/http"
	"time"

	"github.com/go-chi/chi/v5"
	"github.com/go-chi/chi/v5/middleware"
	"github.com/rs/zerolog"

	"github.com/digkill/billing-credit-system/risk-engine/internal/config"
	"github.com/digkill/billing-credit-system/risk-engine/internal/schedule"
	"github.com/digkill/billing-credit-system/risk-engine/internal/score"
)

type Server struct {
	cfg        config.Config
	log        zerolog.Logger
	scoreSvc   *score.Service
	scheduler  *schedule.Generator
	httpServer *stdhttp.Server
}

func New(cfg config.Config, log zerolog.Logger, scoreSvc *score.Service, scheduler *schedule.Generator) *Server {
	router := chi.NewRouter()
	router.Use(middleware.RequestID)
	router.Use(middleware.RealIP)
	router.Use(middleware.Logger)
	router.Use(middleware.Recoverer)

	srv := &Server{
		cfg:       cfg,
		log:       log,
		scoreSvc:  scoreSvc,
		scheduler: scheduler,
	}

	router.Get("/healthz", srv.handleHealth)
	router.Post("/risk/evaluate", srv.handleEvaluateRisk)
	router.Post("/schedule/generate", srv.handleGenerateSchedule)

	srv.httpServer = &stdhttp.Server{
		Addr:         ":" + fmt.Sprint(cfg.HTTPPort),
		Handler:      router,
		ReadTimeout:  10 * time.Second,
		WriteTimeout: 10 * time.Second,
		IdleTimeout:  60 * time.Second,
	}

	return srv
}

func (s *Server) Start() error {
	s.log.Info().Msgf("http server listening on :%d", s.cfg.HTTPPort)
	if err := s.httpServer.ListenAndServe(); err != nil && err != stdhttp.ErrServerClosed {
		return err
	}

	return nil
}

func (s *Server) Shutdown(ctx context.Context) error {
	s.log.Info().Msg("shutting down http server")
	return s.httpServer.Shutdown(ctx)
}

func (s *Server) handleHealth(w stdhttp.ResponseWriter, r *stdhttp.Request) {
	w.WriteHeader(stdhttp.StatusOK)
	_, _ = w.Write([]byte("ok"))
}

func (s *Server) handleEvaluateRisk(w stdhttp.ResponseWriter, r *stdhttp.Request) {
	var payload map[string]any
	if err := json.NewDecoder(r.Body).Decode(&payload); err != nil {
		httpError(w, stdhttp.StatusBadRequest, "invalid payload")
		return
	}

	score, err := s.scoreSvc.EvaluateRisk(r.Context(), payload)
	if err != nil {
		s.log.Error().Err(err).Msg("evaluate risk failed")
		httpError(w, stdhttp.StatusInternalServerError, "could not evaluate risk")
		return
	}

	writeJSON(w, stdhttp.StatusOK, map[string]any{"score": score})
}

func (s *Server) handleGenerateSchedule(w stdhttp.ResponseWriter, r *stdhttp.Request) {
	var req struct {
		Principal int64 `json:"principal"`
		Term      int   `json:"term_months"`
	}
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		httpError(w, stdhttp.StatusBadRequest, "invalid payload")
		return
	}

	installments, err := s.scheduler.Generate(r.Context(), req.Principal, req.Term)
	if err != nil {
		httpError(w, stdhttp.StatusBadRequest, err.Error())
		return
	}

	writeJSON(w, stdhttp.StatusOK, map[string]any{"installments": installments})
}

func httpError(w stdhttp.ResponseWriter, status int, message string) {
	writeJSON(w, status, map[string]string{"error": message})
}

func writeJSON(w stdhttp.ResponseWriter, status int, payload any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(payload)
}
