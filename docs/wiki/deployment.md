# Deployment Guide

This guide covers local development, staging/production deployment, and the supporting automation.

## 1. Environments

| Environment | Purpose                    | Hosting            | Notes |
|-------------|----------------------------|--------------------|-------|
| Local       | Developer workstations      | Docker Compose     | `make up` brings entire stack online. |
| Staging     | Pre-production validation   | Kubernetes (k8s)   | Mirrors prod topology; enables integration testing. |
| Production  | Customer-facing environment | Kubernetes (HA)    | Multi-AZ, managed PostgreSQL/Redis. |

## 2. Local Development

```bash
make init       # pull images & build local Docker images
make up         # start postgres, redis, rabbitmq, services
make migrate    # run Doctrine migrations inside CRM API container
```

Access points:
- CRM API: `http://localhost:8080`
- React UI (hot reload): `http://localhost:5173`
- RabbitMQ management: `http://localhost:15672` (guest/guest)
- Keycloak: `http://localhost:8090`

Stop services with `make down` or `make restart` to refresh.

## 3. Continuous Integration (CI)

GitHub Actions workflow `.github/workflows/ci.yml` performs:
1. **CRM API Job** – composer install, phpstan (if available), Pest/PHPUnit tests.
2. **Risk Engine Job** – Go 1.22 setup, `go test ./...`.
3. **Frontend Job** – Node 20 setup, `npm ci`, linting/testing placeholders.

Future enhancements: caching (composer/npm/go modules), security scans (Trivy, Snyk), container builds.

## 4. Deployment Pipeline (Target)

### 4.1 Build Artifacts
- CRM API: built into PHP-FPM container image (see `apps/crm-api/Dockerfile`).
- Risk Engine: compiled static binary (`go build`), packaged into minimal Docker image.
- Web UI: `npm run build` produces static assets served by Nginx.

### 4.2 Container Registry
- Push images to registry (e.g., GitHub Container Registry) with tags: `crm-api:<git-sha>`, `risk-engine:<git-sha>`, `web:<git-sha>`.

### 4.3 Kubernetes Deployment
- Helm charts (to be added under `deploy/helm`) encapsulate deployments, services, ingress.
- Environment-specific values define secrets (DB URLs, RabbitMQ credentials, OIDC client IDs).
- Use ArgoCD or Flux for GitOps deployment, or GitHub Actions for direct `kubectl` apply.

### 4.4 Database & Migrations
- Doctrine migrations executed via CI job or Kubernetes Job on release.
- Risk Engine migrations (future) run via SQL migration tool (goose/migrate).

## 5. Configuration Management

| Service     | Key Variables                                                  |
|-------------|----------------------------------------------------------------|
| CRM API     | `APP_ENV`, `DATABASE_URL`, `MESSENGER_TRANSPORT_DSN`, `REDIS_URL`, `APP_SECRET`. |
| Risk Engine | `RISK_HTTP_PORT`, `RISK_POSTGRES_DSN`, `RISK_RABBITMQ_URL`, `RISK_REDIS_URL`, `RISK_LOG_LEVEL`. |
| Web         | `VITE_API_URL`, `VITE_KEYCLOAK_REALM`, `VITE_KEYCLOAK_CLIENT_ID` (planned). |

Use Kubernetes Secrets/Vault to store sensitive values. Keep `.env` solely for development defaults.

## 6. Secrets & Certificates

- Generate JWT signing keys for Keycloak.
- Issue mTLS certificates for service-to-service communication (istio/Linkerd or cert-manager).
- Store database credentials and RabbitMQ secrets in Vault; mount through CSI driver or env vars.

## 7. Rolling Out Changes

1. Merge PR → CI builds and pushes container images.
2. Tagged release triggers CD workflow.
3. Helm upgrade applies new version with rolling deployment strategy (`maxUnavailable=0`, `maxSurge=1`).
4. Post-deploy smoke tests (health checks, synthetic transactions).

## 8. Disaster Recovery

- PostgreSQL: automated backups (pgBackRest or managed service snapshots). Document restore drills.
- Redis: enable persistence (AOF) or rely on managed service with backups.
- RabbitMQ: mirrored queues (HA) in production.
- Maintain infrastructure as code (Terraform) for reproducibility.

Keep this guide aligned with actual pipelines. Every time deployment tooling or infra changes, update the respective sections.

