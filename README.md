# Docker Starter Kit

A production-ready **Laravel 12 + React 19 + Inertia v2** starter kit, fully Dockerized with [ServerSideUp PHP](https://serversideup.net/open-source/docker-php/) images. Everything you need to build modern, AI-powered web applications — batteries included.

## What's Included

### Backend

- **[Laravel 12](https://laravel.com/)** — PHP framework
- **[Laravel Fortify](https://laravel.com/docs/fortify)** — Headless authentication (login, register, password reset, email verification, two-factor auth)
- **[Laravel Horizon](https://laravel.com/docs/horizon)** — Queue management with dashboard
- **[Laravel Reverb](https://laravel.com/docs/reverb)** — Real-time WebSocket broadcasting
- **[Laravel AI SDK](https://laravel.com/docs/ai-sdk)** — Unified API for OpenAI, Anthropic, Gemini, and 10+ AI providers (agents, tools, structured output, embeddings, images, audio)
- **[Laravel MCP](https://laravel.com/docs/mcp)** — Model Context Protocol server for AI client integrations
- **[Laravel Wayfinder](https://laravel.com/docs/wayfinder)** — Type-safe route generation for TypeScript
- **[Pest 4](https://pestphp.com/)** — Testing framework
- **[Laravel Pint](https://laravel.com/docs/pint)** — Code style fixer

### Frontend

- **[React 19](https://react.dev/)** with React Compiler
- **[Inertia.js v2](https://inertiajs.com/)** — SPA without the complexity (deferred props, prefetching, polling, infinite scroll)
- **[Tailwind CSS v4](https://tailwindcss.com/)** — Utility-first CSS
- **[Vite 7](https://vite.dev/)** — Lightning-fast HMR
- **[Lucide Icons](https://lucide.dev/)** — Icon library
- **[Base UI](https://base-ui.com/)** — Accessible, unstyled UI primitives
- **[Laravel Echo](https://laravel.com/docs/broadcasting#client-side-installation)** — Real-time event listening

### Infrastructure (Docker)

| Service                     | Image                            | Port            |
|-----------------------------|----------------------------------|-----------------|
| **App** (Nginx + PHP-FPM)   | `serversideup/php:8.5-fpm-nginx` | `80`            |
| **Horizon** (Queue Worker)  | `serversideup/php:8.5-fpm-nginx` | —               |
| **Scheduler** (Cron)        | `serversideup/php:8.5-fpm-nginx` | —               |
| **Reverb** (WebSocket)      | `serversideup/php:8.5-fpm-nginx` | `9001`          |
| **PostgreSQL 18**           | `postgres:18-alpine`             | `5432`          |
| **Redis**                   | `redis:alpine`                   | `6379`          |
| **MinIO** (S3 Storage)      | `minio/minio`                    | `9000` / `8900` |
| **Mailpit** (Email Testing) | `axllent/mailpit`                | `8025` / `1025` |

### AI Providers (pre-configured)

All providers are ready to use — just add your API key:

Anthropic, OpenAI, Gemini, Groq, DeepSeek, Mistral, Cohere, xAI, ElevenLabs, Jina, VoyageAI, OpenRouter, Ollama

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine + Compose)
- [Node.js](https://nodejs.org/) >= 20
- [Composer](https://getcomposer.org/)

## Getting Started

**1. Clone the repository:**

```bash
git clone https://github.com/baconfy/docker-starter-kit.git
cd docker-starter-kit
```

**2. Install dependencies:**

```bash
composer install
npm install
```

**3. Set up environment:**

```bash
cp .env.example .env
php artisan key:generate
```

**4. Start everything:**

```bash
composer dev
```

This single command will:

- Start all Docker containers (PostgreSQL, Redis, MinIO, Mailpit, etc.)
- Wait for all services to be healthy
- Create the MinIO storage bucket
- Run `migrate:fresh --seed` (reset and re-seed the database)
- Start Vite dev server with HMR

> **Note:** Every time you run `composer dev`, the database is completely reset via `migrate:fresh`. This means all data is dropped and recreated from your seeders. Make sure your seeders are well-configured to recreate any data you need for development.

**5. Open the app:**

- **App:** [http://localhost](http://localhost)
- **Horizon Dashboard:** [http://localhost/horizon](http://localhost/horizon)
- **Mailpit:** [http://localhost:8025](http://localhost:8025)
- **MinIO Console:** [http://localhost:8900](http://localhost:8900)

### Default Credentials

| Service               | Username       | Password    |
|-----------------------|----------------|-------------|
| **App** (seeded user) | `root@app.com` | `password`  |
| **PostgreSQL**        | `baconfy`      | `secret`    |
| **MinIO**             | `baconfy`      | `secret123` |

## Commands

| Command              | Description                                          |
|----------------------|------------------------------------------------------|
| `composer dev`       | Start all services + Vite (fresh database each time) |
| `composer dev:stop`  | Stop all services                                    |
| `composer dev:reset` | Destroy all containers and volumes, then start fresh |
| `composer dev:fresh` | Same as `dev` with `migrate:fresh --seed`            |
| `composer test`      | Run lint + tests                                     |
| `composer lint`      | Fix code style with Pint                             |

## Port Customization

All ports are configurable via environment variables to avoid conflicts:

```env
FORWARD_APP_PORT=80
FORWARD_DB_PORT=5432
FORWARD_REDIS_PORT=6379
FORWARD_REVERB_PORT=9001
FORWARD_MINIO_PORT=9000
FORWARD_MINIO_CONSOLE_PORT=8900
FORWARD_MAILPIT_PORT=8025
FORWARD_MAILPIT_SMTP_PORT=1025
```

## Architecture

The project uses a **two-file Docker Compose pattern**:

- **`docker-compose.yml`** — Production base with multi-stage Dockerfile build, OPcache enabled, healthchecks, and auto-migrations.
- **`docker-compose.dev.yml`** — Dev overrides that mount your local code, disable OPcache, expose database/Redis ports, and add Mailpit.

### Dockerfile (Multi-Stage)

```
Stage 1: node:22-alpine           → Build frontend assets (Vite)
Stage 2: serversideup/php:8.5-cli → Install Composer dependencies
Stage 3: serversideup/php:8.5-fpm-nginx → Production image
```

### Healthchecks

All services have healthchecks configured. The `composer dev` command uses `--wait` to ensure everything is healthy before running migrations.

## Production

Build and deploy with the production compose file:

```bash
docker compose up -d --build --wait
```

The production image includes:

- OPcache enabled
- Auto-migrations on startup
- Auto storage link creation
- Optimized Composer autoloader
- Pre-built frontend assets

## License

[MIT](LICENSE)
