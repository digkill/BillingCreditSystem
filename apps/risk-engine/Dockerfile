FROM golang:1.22-alpine AS builder

WORKDIR /app

COPY go.mod go.sum ./
RUN go mod download

COPY . .

RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build -o risk-engine ./cmd/risk-engine

FROM alpine:3.20

WORKDIR /srv

COPY --from=builder /app/risk-engine /usr/local/bin/risk-engine

ENV RISK_ENGINE_CONFIG=/etc/risk-engine/config.yaml

CMD ["risk-engine"]
