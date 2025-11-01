package config

import (
	"fmt"
	"time"

	"github.com/kelseyhightower/envconfig"
)

type Config struct {
	HTTPPort        int           `envconfig:"HTTP_PORT" default:"8081"`
	PostgresDSN     string        `envconfig:"POSTGRES_DSN" default:"postgres://risk_engine:risk_engine@localhost:5432/risk_engine?sslmode=disable"`
	RabbitURL       string        `envconfig:"RABBITMQ_URL" default:"amqp://guest:guest@localhost:5672/"`
	RedisURL        string        `envconfig:"REDIS_URL" default:"redis://localhost:6379/1"`
	ShutdownTimeout time.Duration `envconfig:"SHUTDOWN_TIMEOUT" default:"15s"`
	LogLevel        string        `envconfig:"LOG_LEVEL" default:"info"`
}

func Load() (Config, error) {
	var cfg Config
	if err := envconfig.Process("RISK", &cfg); err != nil {
		return Config{}, fmt.Errorf("load risk config: %w", err)
	}

	return cfg, nil
}
