# Setup guide — AI Agent Platform

This document walks through installing and running the project locally and in Docker. For a project overview, see [README.md](README.md). For the roadmap, see [implementation_plan.md](implementation_plan.md).

---

## 1. Prerequisites

| Tool | Notes |
| --- | --- |
| PHP | 8.2 or newer, with extensions: `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `curl`, `intl`, `zip` (match your DB driver: `pdo_mysql` or `pdo_sqlite`) |
| Composer | 2.x |
| Node.js | 20+ (22+ matches CI) |
| Database | MySQL 8 for normal development, or SQLite for quick trials / PHPUnit defaults |
| Git | For cloning the repository |

Optional: Redis (recommended for production queues and cache), [k6](https://k6.io/) for load smoke tests.

---

## 2. Clone and install PHP dependencies

```bash
git clone <your-repo-url> Laravel-AI-Agent-Platform
cd Laravel-AI-Agent-Platform
composer install
```

For production-like installs without dev tools (smaller vendor tree):

```bash
composer install --no-dev --optimize-autoloader
```

---

## 3. Environment file

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` at minimum:

- `APP_URL` — must match how you open the app (e.g. `http://127.0.0.1:8000` if using `php artisan serve`).
- Database block — see section 4.
- At least one AI provider key for chat to work:
  - `ANTHROPIC_API_KEY` and/or `OPENAI_API_KEY`, or use local `OLLAMA_HOST` with `AI_DEFAULT_MODEL` set to an Ollama-backed alias from `/api/models`.

Never commit `.env` or real secrets.

---

## 4. Database

### Option A — MySQL (typical local)

1. Create a database and user (example):

   ```sql
   CREATE DATABASE ai_agent_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'app'@'%' IDENTIFIED BY 'your_password';
   GRANT ALL ON ai_agent_platform.* TO 'app'@'%';
   FLUSH PRIVILEGES;
   ```

2. In `.env`:

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ai_agent_platform
   DB_USERNAME=app
   DB_PASSWORD=your_password
   ```

3. Migrate:

   ```bash
   php artisan migrate
   ```

Optional: `php artisan db:seed` if you use project seeders.

### Option B — SQLite (quick start)

```env
DB_CONNECTION=sqlite
# DB_DATABASE can point to a file path; PHPUnit uses in-memory sqlite automatically
```

Create the file if you use a file DB:

```bash
touch database/database.sqlite
```

Then:

```bash
php artisan migrate
```

---

## 5. Sessions, cache, and queues

Default `.env.example` uses the database for session, cache, and queue (`QUEUE_CONNECTION=database`). After migrating, those tables exist.

For **memory extraction**, **heartbeats**, and **async tool tasks**, run a worker:

```bash
php artisan queue:work
```

Run the **scheduler** (or use `schedule:work` in development):

```bash
php artisan schedule:work
```

On a server, add a cron entry: `* * * * * cd /path-to-app && php artisan schedule:run >> /dev/null 2>&1`

For better throughput in production, switch to Redis:

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

(and ensure Redis is running and `REDIS_*` is set).

---

## 6. Frontend (Vite)

```bash
npm install
```

Development (hot reload):

```bash
npm run dev
```

Production build (deploy / Docker image):

```bash
npm run build
```

Ensure `APP_URL` and Vite’s dev server URL align with how you proxy or open the app. The Laravel app serves the built assets from `public/build` after `npm run build`.

---

## 7. Run the application

Terminal 1 — HTTP server:

```bash
php artisan serve
```

Terminal 2 — Vite (during development):

```bash
npm run dev
```

Terminal 3 (optional) — queue:

```bash
php artisan queue:work
```

Open the URL shown by `artisan serve` (often `http://127.0.0.1:8000`), register a user, and use the chat UI.

### One-shot dev stack (Composer script)

If you have dev dependencies installed:

```bash
composer run dev
```

This runs the server, queue listener, logs, and Vite together (see `composer.json`).

### Full automated first-time setup

```bash
composer run setup
```

This runs `composer install`, ensures `.env`, generates `APP_KEY`, migrates, installs npm packages, and runs `npm run build`. You still need to fill AI keys and DB credentials in `.env` before chat will call real models.

---

## 8. Verify the install

1. `php artisan about` — Laravel environment summary.
2. `GET /up` — health check (JSON/up).
3. `php artisan test` — automated tests.
4. Log in via the SPA and send a chat message (with valid provider credentials).

---

## 9. Optional: realtime (Echo / Soketi / Pusher)

To enable broadcasting for realtime UI updates:

1. Set `BROADCAST_CONNECTION=pusher` in `.env`.
2. Fill `PUSHER_*` server variables and matching `VITE_PUSHER_*` for the browser.
3. Rebuild frontend after changing `VITE_*` variables: `npm run build` or restart `npm run dev`.

[Soketi](https://docs.soketi.app/) is a common self-hosted Pusher-compatible option.

---

## 10. Optional: channel webhooks (LINE, Telegram, etc.)

Configure connections in the app under **Channels**. Each provider needs credentials in the UI; webhook URLs are generated per connection. Production requires HTTPS for some providers (e.g. LINE). See rate limits in `AppServiceProvider` for webhook throttling.

---

## 11. Optional: Docker (production-oriented)

From the project root, with `.env` tuned for the compose file (e.g. `DB_HOST=mysql`, Redis if you enable it):

```bash
docker compose -f docker-compose.prod.yml up -d
```

Run migrations inside the app container on first deploy:

```bash
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

The `app` service exposes port `8080` by default (`APP_PORT`). Workers use the same image with a different command.

---

## 12. Optional: monitoring and debugging

| Tool | Purpose |
| --- | --- |
| **Sentry** | Set `SENTRY_LARAVEL_DSN` in `.env`; errors report when DSN is present. |
| **Telescope** | Dev dependency. Set `TELESCOPE_ENABLED=true` locally, run migrations (Telescope tables), visit `/telescope`. Keep disabled in production unless tightly gated. |
| **Usage analytics** | In-app **Usage** page; estimates use `config/pricing.php`. |

---

## 13. Security checklist (before production)

- `APP_ENV=production`, `APP_DEBUG=false`.
- Stable `APP_KEY` (never rotate casually if you rely on encrypted user API keys).
- `SECURITY_PROMPT_INJECTION_MODE` and related keys in `.env` (see `.env.example`).
- Restrict `SKILLS_HTTP_WEBHOOK_ALLOWED_HOSTS` in production.
- Strong DB passwords and restricted MySQL user permissions.

---

## 14. Troubleshooting

| Symptom | Things to check |
| --- | --- |
| 500 on first request | `php artisan config:clear`, missing `APP_KEY`, or DB connection errors in `storage/logs/laravel.log`. |
| Chat errors / timeout | Provider API keys, model name vs `AI_DEFAULT_MODEL`, network to Anthropic/OpenAI/Ollama. |
| Vite assets 404 | Run `npm run dev` or `npm run build`; clear browser cache. |
| Queue jobs never run | `php artisan queue:work` or Horizon; `QUEUE_CONNECTION` and Redis/DB. |
| Session issues cross-domain | `SESSION_DOMAIN`, `SANCTUM` stateful domains, HTTPS/cookies. |

For framework docs: [Laravel 12 documentation](https://laravel.com/docs/12.x).
