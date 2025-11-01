# Operations & Maintenance

This section provides day-to-day runbooks, monitoring checklists, and guidance on keeping the Billing Credit System healthy.

## 1. Monitoring & Observability

### 1.1 Metrics
- **CRM API**
  - HTTP request duration (`symfony_http_request_duration_seconds`).
  - Doctrine DB metrics (query counts, slow query alerts).
  - Messenger worker throughput (jobs processed, failures).
- **Risk Engine**
  - HTTP latency for `/risk/evaluate` and `/schedule/generate`.
  - RabbitMQ consumer lag (messages ready/unacked).
  - Custom metrics: risk score distribution, schedule generation duration.
- **Infrastructure**
  - PostgreSQL replication lag, connection pool usage.
  - Redis memory usage, cache hit ratio.
  - RabbitMQ queue depth, dropped messages.

Exporters:
- Symfony + PHP: use Symfony Metrics Bundle or OpenTelemetry instrumentation.
- Go: expose Prometheus endpoint via `chi` middleware.
- RabbitMQ: enable management plugin metrics endpoint.

### 1.2 Logging
- **CRM API**: Monolog → Loki/ELK. Structure logs with context (aggregate IDs, command names).
- **Risk Engine**: Zerolog (JSON) forwarded via Fluent Bit to log storage.
- **Frontend**: Capture console errors with Sentry.

### 1.3 Tracing
- Adopt OpenTelemetry SDKs:
  - Symfony instrumentation via `open-telemetry/opentelemetry-php`.
  - Go instrumentation via `go.opentelemetry.io/otel`.
- Trace key flows: customer onboarding, loan submission, risk evaluation.

## 2. Runbooks

### 2.1 Messenger Queue Backlog
1. Check RabbitMQ queue `messages` backlog.
2. Inspect CRM worker logs (`docker compose logs crm-worker` or Kubernetes logs).
3. Verify database connectivity.
4. Scale workers horizontally (increase replicas) or requeue failed messages.
5. If message poison-pill, move to dead-letter and create bug ticket.

### 2.2 Risk Engine Degradation
1. Verify `/healthz` endpoint.
2. Review metrics for CPU/memory spikes.
3. Check RabbitMQ connection (consumer heartbeat).
4. Restart pod with rolling deployment if needed.
5. Notify stakeholders if risk evaluations delayed > SLA (configurable alert).

### 2.3 Database Migration Failure
1. Identify failing migration version (`doctrine:migrations:list`).
2. Rollback deployment (if possible) to last stable build.
3. Fix migration script locally, re-run `make migrate`, regenerate migration.
4. Apply fix through CI pipeline.

## 3. Backup & Recovery

- **PostgreSQL**
  - Nightly full backups, 5-min WAL archiving.
  - Perform quarterly restore drills to standby environment.
- **Redis**
  - Enable AOF with hourly snapshots (or use managed service with SLA).
- **RabbitMQ**
  - Export queue definitions and policies.
  - Mirror queues across nodes; ensure quorum queues for critical messages.
- **Keycloak**
  - Export realm configuration, maintain admin credentials in password manager.

## 4. Security & Compliance

- Enforce TLS everywhere (`Let's Encrypt` via cert-manager; mTLS with service mesh).
- Rotate secrets regularly; no long-lived credentials in repos.
- Implement audit logging:
  - CRM API: log admin actions (loan approvals, customer status changes).
  - Risk Engine: store scoring decision inputs/outputs with hash for tamper detection.
- Regular vulnerability scans (Trivy, Dependabot, gosec, npm audit).

## 5. Maintenance Windows & Upgrades

- Schedule quarterly dependency upgrades (Symfony minor, Go patch, Node LTS).
- Apply database migrations during maintenance windows, with read-only mode if needed.
- Update RabbitMQ/Redis with rolling restarts; monitor for connection churn.

## 6. Documentation Hygiene

- Keep [README](../README.md) updated alongside code changes.
- Record major architectural decisions in ADRs (`docs/wiki/adr/`).
- Update runbooks after each incident postmortem to reflect lessons learned.

Maintaining operational excellence requires continuous feedback. Encourage engineers to append or refine runbooks whenever new scenarios arise.

