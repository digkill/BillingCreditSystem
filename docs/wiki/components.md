# Platform Components

This page drills into each service and infrastructure component, describing responsibilities, interfaces, and dependencies.

## 1. Applications

### 1.1 CRM API (Symfony)
- **Location:** `apps/crm-api`
- **Purpose:** Authoritative system for customer dossiers, loan applications, and payment schedules.
- **Architecture:** Domain-Driven Design with separate bounded contexts.
  - `Customer`: aggregates, repositories, value objects (e.g., `Email`).
  - `Loan`: loan lifecycle, events (`LoanApplicationSubmitted`, `LoanActivated`).
  - `Payment`: scheduled instalments, status transitions.
- **Interfaces:**
  - REST/GraphQL (planned) endpoints under `/api`.
  - Message bus (Symfony Messenger) for commands/events.
  - Outbound events to RabbitMQ (`async` transport).
- **Persistence:** Doctrine ORM (PostgreSQL). Migration `Version20241001000000` seeds base schema.
- **Testing:** Pest unit tests; phpstan static analysis; future functional tests via API Platform/Panther.

### 1.2 Risk Engine (Go)
- **Location:** `apps/risk-engine`
- **Purpose:** Automated risk scoring and repayment schedule generation.
- **Packages:**
  - `internal/http`: chi router providing `/healthz`, `/risk/evaluate`, `/schedule/generate`.
  - `internal/messaging`: RabbitMQ consumer for `loan_application.submitted` events.
  - `internal/schedule`: deterministic instalment generator (unit tested).
  - `internal/score`: risk scoring service (currently stubbed at `0.5`).
  - `internal/app`: wiring for configuration, logger, HTTP server, and consumer lifecycle.
- **Config:** Environment variables prefixed `RISK_` (`RISK_HTTP_PORT`, `RISK_POSTGRES_DSN`, etc.).
- **Dependencies:** PostgreSQL (future persistence), RabbitMQ, Redis (caching), Zerolog for logging.

### 1.3 Operations Web (React)
- **Location:** `apps/web`
- **Purpose:** Operator-facing CRM dashboard for loan portfolio management.
- **Stack:** Vite, React 19, TypeScript, Tailwind CSS (with shadcn-style components), React Router, React Query.
- **Structure:**
  - `app/`: global providers & router.
  - `pages/dashboard`: landing KPIs + loan table (mock data placeholder).
  - `widgets/layout`: layout shell, loan table widget.
  - `shared/components/ui`: reusable primitives (Button, Badge).
- **Environment:** `VITE_API_URL` used to point at CRM API. Additional `VITE_*` secrets stored in `.env` files or Vault.
- **Testing:** ESLint + (future) Vitest/Playwright.

## 2. Supporting Services

| Service   | Purpose                                           | Notes |
|-----------|---------------------------------------------------|-------|
| PostgreSQL| Primary relational database for CRM & Risk Engine | Two databases (`crm_api`, `risk_engine`); migrations managed per service. |
| Redis     | Cache, session store, rate limiting               | Symfony uses `REDIS_URL`; Risk Engine via `RISK_REDIS_URL`. |
| RabbitMQ  | Asynchronous event transport & worker queues      | Exchanges/queues: `messages` (default), `loan_applications` (custom). |
| Keycloak  | Identity provider (OIDC)                          | Runs in dev via Docker Compose at `http://localhost:8090`. |
| Nginx     | Reverse proxy for CRM & static web assets         | Configurations under `docker/nginx/`.

## 3. Data Stores

- **CRM Schema**
  - `customers`: stores canonical customer profile.
  - `loan_applications`: principal, terms, lifecycle timestamps.
  - `payments`: scheduled instalments with expected/paid amounts.
  - Future: dedicated schema per bounded context (`accounting`, `notification`).

- **Risk Engine Schema (future)**
  - `risk_assessments`: store scoring results + audit.
  - `amortization_schedules`: persisted schedule snapshots.

- **Analytics**
  - Event streams can be replicated to ClickHouse/BigQuery for reporting. Not yet implemented.

## 4. Messaging Contracts

- **Domain Events (emitted by CRM API):**
  - `customer.registered`
  - `customer.status_changed`
  - `loan_application.submitted`
  - `loan_application.approved`
  - `loan.activated`
  - `payment.registered`
  - `payment.status_changed`

- **Integration Events (planned):**
  - `schedule.generated` from Risk Engine.
  - `risk.assessment.completed` from Risk Engine.
  - `accounting.posting.created` (future Accounting service).

Event payloads follow snake_case field names; event IDs use UUIDv7; timestamps in ISO8601.

## 5. External Integrations (Roadmap)

- **Credit Bureaus / Scoring APIs:** Risk Engine adapters for third-party scoring.
- **Payment Gateways:** For ingesting actual repayment confirmations.
- **Notification Providers:** Email/SMS via worker processes.

Keep this page updated with schemas, endpoint contracts, and integration specifics as the platform matures.

