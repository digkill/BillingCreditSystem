# System Architecture

## 1. High-Level Overview

The Billing Credit System is a polyglot monorepo that orchestrates the full lifecycle of retail loans — from customer onboarding to disbursement, repayment, and reporting. Three primary applications interact with a shared infrastructure layer:

```
+----------------------+      RabbitMQ       +--------------------+
|  CRM API (Symfony)   |  <----------------> |  Risk Engine (Go)  |
|  - Customer/Loan DDD |       Events        |  - Scoring &       |
|  - REST/GraphQL API  |                     |    Schedule calc   |
+----------+-----------+                     +----------+---------+
           |                                         ^
           v                                         |
+----------+-----------+                             |
|   Web Frontend       |  REST/GraphQL               |
| (React + Tailwind)   | ---------------------------+
+----------------------+               API calls
```

Supporting services include PostgreSQL (transactional storage), Redis (cache/session), Keycloak (IAM), and observability tooling (Prometheus/Grafana, Loki/ELK).

## 2. Monorepo Structure

```
apps/
  crm-api/         # Symfony 7 bounded contexts (Customer, Loan, Payment)
  risk-engine/     # Go 1.22 microservice (risk scoring, schedule generation)
  web/             # Vite + React + Tailwind operational UI

docker/            # Service-specific Docker assets (nginx configs, PHP ini, etc.)
docker-compose.yml # Local orchestration
.github/workflows/ # CI pipelines
```

Shared conventions:

- **DDD layering:** Each bounded context exposes `Domain`, `Application`, `Infrastructure` packages.
- **Event-driven integration:** Domain events are emitted onto RabbitMQ using the Outbox/Messenger pattern.
- **Infrastructure-as-code:** Local environments rely on Docker Compose; production targets Kubernetes via Helm charts (future work).

## 3. Domain Boundaries

### Customer Context
- Manages onboarding, KYC status, identity management.
- Emits events: `customer.registered`, `customer.status_changed`.

### Loan Context
- Governs loan application lifecycle (`Draft → Submitted → Approved → Active → Closed/Rejected`).
- Integrates with Risk Engine for automated approvals and schedule generation.
- Emits events: `loan_application.submitted`, `loan_application.approved`, `loan.activated`.

### Payment Context
- Tracks repayment schedules, payment postings, and status transitions (`Scheduled`, `Due`, `Paid`, `Overdue`).
- Feeds Accounting context (future) via events.

## 4. Data Flow

1. **Customer Registration**
   - Web UI submits data to CRM API.
   - CRM API persists customer, emits `customer.registered` event.

2. **Loan Application Submission**
   - CRM API creates `LoanApplication` aggregate, emits `loan_application.submitted`.
   - Risk Engine consumer receives event → calculates risk score & repayment schedule → posts result back via API (future) or publishes derived events.

3. **Loan Activation & Repayments**
   - After approval, activation triggers creation of payment schedule entries.
   - Payment events -> used by Accounting & Reporting.

## 5. Cross-Cutting Concerns

- **Authentication/Authorisation:** Keycloak issues tokens (OIDC). Symfony validates JWT; frontend uses PKCE flow. Service-to-service traffic uses mTLS (planned).
- **Configuration Management:** `.env` for local, environment variables in production. Secrets managed via Vault or Kubernetes Secrets.
- **Telemetry:** OpenTelemetry exporters embedded in services; metrics scraped by Prometheus; logs shipped to ELK/Loki.
- **Error Handling & Resilience:** Doctrine transactions ensure aggregate consistency; retry policies on consumers; Dead-letter queues via RabbitMQ bindings.

## 6. Scaling & Modularity

- Services are deployable independently (Symfony worker, Go binary, static frontend).
- Read models can be materialised in separate stores (e.g., ClickHouse) using event streams.
- Future microservices (Accounting, Notification) can extend the event contracts without tight coupling.

## 7. Technology Decisions

| Area                | Choice                              | Rationale |
|---------------------|-------------------------------------|-----------|
| Backend Framework   | Symfony 7 + Doctrine ORM            | Mature ecosystem, first-class CQRS support |
| Messaging           | RabbitMQ + Symfony Messenger        | Robust AMQP semantics, delayed retries |
| Risk Processing     | Go 1.22 + chi                       | Lightweight concurrency, strong typing |
| Frontend            | React + Vite + Tailwind + shadcn/ui | Fast DX, component consistency |
| Database            | PostgreSQL 16                       | ACID transactions, partitioning capabilities |
| Caching             | Redis 7                             | Sessions, scoring cache, rate limiting |
| AuthN/AuthZ         | Keycloak 25                         | OIDC, fine-grained RBAC |

Keep this document evergreen. Update flows when integrating new contexts (Accounting, Notification) or moving to production infrastructure.

