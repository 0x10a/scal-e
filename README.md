# Scal-e CDP
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Redis](https://img.shields.io/badge/Redis-7-DC382D?logo=redis&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green.svg)

Minimal Customer Data Platform built with native PHP 8, MySQL, Redis, and a custom MVC structure.

## Setup

1. Clone the repository:

```bash
git clone https://github.com/0x10a/scal-e.git
cd scal-e
```

2. Copy and edit `.env` if needed:

```bash
cp .env.example .env
```

3. Start the stack:

```bash
docker compose up -d --build
```

4. Install PHP dependencies:

```bash
docker compose exec web composer install
```

5. Open the app in your browser at `http://localhost`.

## Run

- Web app: `http://localhost`
- API worker: runs automatically in Docker when `QUEUE_ENABLED=true`
- Tests: `./vendor/bin/phpunit`

## API

All API routes require `X-Api-Key` or `?api_key=`.

- `POST /api/events`
  - Example:

```bash
curl -X POST http://localhost/api/events \
  -H 'X-Api-Key: your-key' \
  -H 'Content-Type: application/json' \
  -d '{"customer":{"email":"alice@example.com","name":"Alice Martin"},"event":"purchase","properties":{"amount":150},"timestamp":"2026-01-15T10:30:00Z"}'
```

- `GET /api/customers?page=1&per_page=15`
- `GET /api/customers/{id}`
- `POST /api/segments/query`
  - Example:

```bash
curl -X POST http://localhost/api/segments/query \
  -H 'X-Api-Key: your-key' \
  -H 'Content-Type: application/json' \
  -d '{"conditions":[{"event":"purchase","property":"amount","operator":">","value":100}]}'
```

Full schema and examples: `public/openapi.yaml` or `public/api-docs.html`.

## Design Decisions

- Native PHP 8 with a small custom MVC, no framework.
- Redis is used for caching, queueing, and simple rate-limiting-ready infrastructure.
- `POST /api/events` can be queued to keep ingestion fast; reads stay synchronous.
- Docker is used for reproducible local setup and CI parity.