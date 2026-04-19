# AI Agent Platform

Laravel 12 + Vue 3 web app for multi-model AI chat, tool execution, channel bots (LINE, Telegram, Slack, Discord), long-term memory, and a skill marketplace—similar in spirit to OpenClaw-style agent platforms.

See [implementation_plan.md](implementation_plan.md) for the full phased roadmap (Phases 1–6). Detailed install steps: [SETUP.md](SETUP.md). **Thai localhost runbook (step-by-step):** [RUN_LOCALHOST_TH.md](RUN_LOCALHOST_TH.md). Thai environment/config reference: [CONFIGURATION_TH.md](CONFIGURATION_TH.md).

## Tech stack

| Layer | Stack |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12, Sanctum, Queues, Scheduler |
| Frontend | Vue 3, Vite, Pinia, Vue Router, Tailwind CSS |
| AI | Anthropic, OpenAI, Ollama (configurable models) |
| Realtime | Laravel Echo + Pusher-compatible (e.g. Soketi) |
| Optional ops | Sentry, Laravel Telescope (dev), Docker Compose |

## Features (high level)

- **Auth:** API token auth via Sanctum; Vue SPA login/register.
- **Chat:** Streaming (SSE) and non-streaming replies; conversations and messages.
- **Tools / skills:** Registry with function calling; built-ins (web search, browser, filesystem, shell, weather, stocks, integrations, etc.); marketplace install/custom skills.
- **Channels:** Webhooks for LINE, Telegram, Slack, Discord with rate limiting.
- **Memory:** User memories, persona/context settings, optional extraction and heartbeat jobs.
- **Phase 6 polish:** Prompt-injection guard, encrypted stored API keys, usage/cost analytics, onboarding modal, production Docker files, Azure Pipelines template, k6 smoke script.

## Requirements

- PHP 8.2+
- Composer
- Node.js 20+ (or 22+ recommended for parity with CI)
- MySQL 8 (or SQLite for local/testing)
- Redis optional (recommended for production queues/cache)

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env` (database, `APP_URL`, AI keys such as `ANTHROPIC_API_KEY` / `OPENAI_API_KEY`, optional `OLLAMA_HOST`).

```bash
php artisan migrate
npm install
npm run dev
```

In another terminal:

```bash
php artisan serve
```

For queue and scheduler features (memory extraction, heartbeats, async tasks), run a queue worker and ensure `php artisan schedule:work` or a system cron hits `schedule:run`.

Composer shortcuts:

- `composer run setup` — install deps, `.env`, key, migrate, npm build
- `composer run dev` — concurrent server, queue, logs, Vite
- `composer run test` — clear config cache and PHPUnit

## Production Docker

Build and run the stack (adjust `.env` for `DB_HOST=mysql`, Redis, etc.):

```bash
docker compose -f docker-compose.prod.yml up -d
```

The image bundles PHP-FPM + Nginx (see `Dockerfile`, `deploy/`). Run migrations against the DB container on first deploy.

## Testing

```bash
php artisan test
```

k6 load smoke (optional): set `BASE_URL` and `SANCTUM_TOKEN`, then run `tests/load/k6-chat-smoke.js`.

## CI

`azure-pipelines.yml` runs Composer, `npm ci` / `npm run build`, migrations on SQLite, and PHPUnit. Extend with your deploy stages and secrets.

## Monitoring & debugging

- **Health:** `GET /up` — use for uptime checks (e.g. Uptime Robot).
- **Sentry:** set `SENTRY_LARAVEL_DSN` in `.env` when ready.
- **Telescope:** dev-only package; set `TELESCOPE_ENABLED=true` locally after `composer install` (includes dev dependencies). Disabled by default in config.

## Security notes

- User messages can be filtered for common prompt-injection patterns (`config/security.php`).
- User API keys in the database use Laravel’s encrypted cast (`APP_KEY` must be stable in production).
- Review channel webhook secrets, skill HTTP webhook allowlists (`SKILLS_HTTP_WEBHOOK_ALLOWED_HOSTS`), and production `APP_DEBUG=false`.

## License

This project is open source under the [MIT license](https://opensource.org/licenses/MIT).
