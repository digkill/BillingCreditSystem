# Billing Credit System Monorepo

This repository hosts the core services and UI for a credit issuance and CRM platform. The codebase is organised as a polyglot monorepo with Symfony (CRM API), Go (Risk Engine), and React/TypeScript (Operations UI).

## Project Layout

- `apps/crm-api` – Symfony 7 CRM/API service following DDD modules (`Customer`, `Loan`, `Payment`) with Doctrine ORM, Messenger (RabbitMQ), and domain events.
- `apps/risk-engine` – Go 1.22 microservice providing risk scoring APIs and consuming loan events from RabbitMQ to generate schedules.
- `apps/web` – Vite + React + TypeScript frontend with Tailwind + shadcn/ui primitives organised by features.
- `docker-compose.yml` – Development orchestration for Postgres, Redis, RabbitMQ, Keycloak, PHP-FPM, Risk engine, and the UI.
- `Makefile` – Common developer tasks (build, lint, tests, migrations).

## Quick Start

```bash
make init        # pull & build container images
make up          # bootstrap the stack
docker compose exec crm-api php bin/console doctrine:migrations:migrate
```

Local tooling shortcuts:

- `make crm-console cmd="cache:clear"` – Run Symfony console commands.
- `make test-backend` / `make test-risk` / `make test-web` – Execute central test suites (Pest, Go tests, ESLint).

## CRM API Highlights (`apps/crm-api`)

- Domain layers split into `Domain`, `Application`, and `Infrastructure` per bounded context.
- Rich aggregates for `Customer`, `LoanApplication`, and `Payment` emit domain events (`CustomerRegistered`, `LoanApplicationSubmitted`, etc.).
- Messenger configured with separate command & event buses (`command.bus`, `event.bus`) and RabbitMQ async transport.
- Doctrine migration `Version20241001000000` seeds core tables (`customers`, `loan_applications`, `payments`).
- Shared value objects (`Money`, embedded `Email`) and repository interfaces wired via service aliases.
- Pest unit coverage for critical invariants (loan activation, payment settlement).

Run tests locally:

```bash
cd apps/crm-api
./vendor/bin/pest
./vendor/bin/phpstan analyse --configuration=phpstan.neon.dist
```

## Risk Engine (`apps/risk-engine`)

- Configuration via `RISK_*` environment variables (envconfig) with zerolog structured logging.
- HTTP API (chi) exposes `/healthz`, `/risk/evaluate`, `/schedule/generate` endpoints.
- RabbitMQ consumer scaffolding ready to react to `LoanApplicationSubmitted` events.
- Deterministic repayment schedule generator with unit tests.

Execute tests:

```bash
cd apps/risk-engine
go test ./...
```

## Frontend (`apps/web`)

- Feature-sliced baseline (`app`, `pages`, `widgets`, `shared`) using React Router, React Query, and Tailwind + shadcn/ui components.
- Dashboard starter page with portfolio KPIs and mock loan table.
- Theme provider scaffolding for light/dark toggling and reusable primitives (`Button`, `Badge`).

Development scripts:

```bash
cd apps/web
npm install
npm run dev
npm run lint
```

## Documentation

- [Architecture & component wiki](docs/wiki/README.md)
- Upcoming ADRs under `docs/wiki/adr/`

## Next Steps

1. **Authentication & Authorisation** – Integrate Keycloak (OIDC) with the frontend and secure Symfony routes (JWT/mTLS between services).
2. **Outbox & Process Management** – Implement Doctrine outbox + worker to guarantee event delivery and model cross-service sagas.
3. **Risk Engine Integration** – Replace stub schedule generation with business rules and persist results back to CRM via REST/gRPC.
4. **Payments & Accounting** – Expand `Payment` aggregates with double-entry ledger support and reconciliation workflows.
5. **CI/CD Hardening** – Extend GitHub Actions with caching, static analysis gates (phpstan strict, go vet, vitest) and container build pipelines.
6. **Observability** – Add OpenTelemetry exporters, Prometheus metrics, and structured tracing across services.
