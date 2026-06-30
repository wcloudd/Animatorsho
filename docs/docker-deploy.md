# Docker Production Deployment

This guide covers building and running Animatorsho with Docker on a production server.

The stack uses **PHP-FPM**, **Nginx**, and **PostgreSQL 16**. See [docs/postgresql-deploy.md](postgresql-deploy.md) for database-level details.

---

## Architecture

```
Internet → Nginx (port 80/443) → PHP-FPM (port 9000) → PostgreSQL (port 5432)
```

| Service     | Image                      | Role                                      |
|-------------|----------------------------|-------------------------------------------|
| `app`       | Built from `Dockerfile`    | PHP-FPM — runs Laravel                   |
| `nginx`     | `nginx:1.27-alpine`        | Serves static assets and proxies PHP     |
| `postgres`  | `postgres:16-alpine`       | PostgreSQL database                       |
| `queue`*    | Same as `app`              | Laravel queue worker (optional)           |
| `scheduler`*| Same as `app`              | Laravel task scheduler (optional)         |

*Optional services are commented out in `docker-compose.yml`. Uncomment to enable.

---

## Files Overview

```
Dockerfile                   Multi-stage: build stage (PHP+Node) → app stage (PHP-FPM)
docker-compose.yml           Service orchestration
.dockerignore                Build context exclusions
docker/nginx/default.conf    Nginx virtual host config
docker/php/php.ini           PHP runtime settings (memory, upload limits, timezone)
docker/php/opcache.ini       OPcache settings (production-tuned)
docker/entrypoint.sh         Container startup script (permissions + asset sync)
```

---

## Prerequisites

- Docker Engine 24+ and Docker Compose v2
- A `.env` file on the server (copy from `.env.example`, do not commit)
- Domain pointing to the server (for HTTPS, add a reverse proxy or TLS terminator in front)

---

## Step 1: Prepare the Environment File

```bash
cp .env.example .env
```

Edit `.env` with production values. See [Required Environment Variables](#required-environment-variables) below.

> **Important**: Values containing spaces or Persian characters **must be quoted**:
> ```
> CARD_TO_CARD_OWNER_NAME="نام صاحب کارت"
> APP_NAME="Animatorsho"
> ```

---

## Step 2: Build the Docker Image

```bash
docker compose build
```

The build runs two stages:

1. **build stage** — installs Composer deps, generates Wayfinder TypeScript types, runs `npm ci` and `npm run build`
2. **app stage** — PHP-FPM image with built assets baked in

Expect the first build to take 3–6 minutes depending on network speed and machine.

To rebuild after a code change:

```bash
docker compose build --no-cache
```

---

## Step 3: Start Services

```bash
docker compose up -d
```

Check all services are healthy:

```bash
docker compose ps
```

Expected output — all services `running` or `healthy`:

```
NAME                  IMAGE                  STATUS
animatorsho-app-1     animatorsho-app        Up (healthy)
animatorsho-nginx-1   nginx:1.27-alpine      Up
animatorsho-postgres-1 postgres:16-alpine    Up (healthy)
```

---

## Step 4: First-Run Commands

Run these **once** on a fresh server. Safe to re-run on redeploys (`migrate --force` skips already-run migrations).

```bash
# Generate application key (fresh server only)
docker compose exec app php artisan key:generate

# Run all database migrations
docker compose exec app php artisan migrate --force

# Seed course catalog and SMS templates (idempotent — safe to re-run)
docker compose exec app php artisan db:seed --class=AnimatorshoCourseSeeder --force
docker compose exec app php artisan db:seed --class=SmsTemplateSeeder --force

# Cache configuration, routes, and views for production performance
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

> Do **not** run `php artisan db:seed` without `--class`. The bare seeder creates a test user.

---

## Step 5: First Admin

After registering via OTP login on the site:

```bash
docker compose exec app php artisan tinker --execute \
  "App\Models\User::where('mobile', '09XXXXXXXXX')->update(['is_admin' => true]);"
```

Replace `09XXXXXXXXX` with the admin's registered mobile number.

---

## Required Environment Variables

Copy `.env.example` to `.env` and fill in every block below. Do not commit `.env`.

### Core Application

| Variable | Production Value |
|----------|-----------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Generate with `php artisan key:generate` |
| `APP_URL` | `https://animatorsho.ir` |
| `FORCE_HTTPS` | `true` |
| `TRUSTED_PROXIES` | Load-balancer IP or `*` |
| `LOG_LEVEL` | `warning` |

### Database (PostgreSQL)

When using the `postgres` service from `docker-compose.yml`:

| Variable | Value |
|----------|-------|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `postgres` ← must match the service name in docker-compose.yml |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `animatorsho` |
| `DB_USERNAME` | `animatorsho_app` |
| `DB_PASSWORD` | Strong password (never commit) |
| `DB_SSLMODE` | `prefer` |

> `DB_HOST=postgres` is hard-coded in `docker-compose.yml` via the `environment:` block and overrides whatever is in `.env`.

### Sessions, Cache, Queue

Default values from `.env.example` work for a single-server deploy:

```dotenv
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### Zarinpal

```dotenv
ZARINPAL_MERCHANT_ID=your-live-merchant-id
ZARINPAL_SANDBOX=false
```

### Card-to-Card

```dotenv
CARD_TO_CARD_NUMBER=6219xxxxxxxxxxxxxxxx
CARD_TO_CARD_OWNER_NAME="نام صاحب کارت"
CARD_TO_CARD_RECEIPT_MAX_KB=5120
```

### SMS / FarazSMS

```dotenv
SMS_DRIVER=farazsms
SMS_ENABLED=true
SMS_ADMIN_MOBILE=09XXXXXXXXX
FARAZSMS_API_KEY=your-api-key
FARAZSMS_SENDER=your-sender-line
```

> Register the OTP pattern with FarazSMS before enabling `SMS_ENABLED=true`. See `docs/postgresql-deploy.md` for details.

### SpotPlayer

```dotenv
SPOTPLAYER_ENABLED=true
SPOTPLAYER_API_KEY=your-api-key
SPOTPLAYER_TEST_MODE=false
```

### Nginx Port

```dotenv
NGINX_PORT=80
```

Change to expose on a different host port (e.g., `8080` if port 80 is taken by a host proxy).

---

## Redeploy Workflow

After pushing new code:

```bash
# Rebuild the app image with the latest code and assets
docker compose build app

# Restart the app (and queue/scheduler if running)
docker compose up -d --no-deps app

# Run new migrations (skips already-applied ones)
docker compose exec app php artisan migrate --force

# Refresh Laravel's caches
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

The `entrypoint.sh` script automatically syncs the newly built `public/build` assets into the shared Nginx volume on each container start.

---

## Queue Worker (Optional)

The app uses `QUEUE_CONNECTION=database`. Jobs include SMS sends and notifications.

To enable, uncomment the `queue` service block in `docker-compose.yml`, then:

```bash
docker compose up -d queue
```

Monitor the worker:

```bash
docker compose logs -f queue
```

Without a running worker, queued jobs accumulate in the `jobs` table and run only when a worker is started. SMS sends are currently synchronous, but enabling a queue worker will process any future queued work immediately.

---

## Scheduler (Optional)

The project schedules `security:prune-events` to run daily (see `routes/console.php`).

**Option A — Docker scheduler service** (uncomment in `docker-compose.yml`):

```bash
docker compose up -d scheduler
```

**Option B — Host cron** (preferred for single-process reliability):

```cron
* * * * * docker compose -f /path/to/app/docker-compose.yml exec -T app php artisan schedule:run >> /dev/null 2>&1
```

Do not run both at the same time.

---

## Persistent Data

| Volume | Contents | Must survive redeployments |
|--------|----------|--------------------------|
| `postgres_data` | All database rows | Yes — back up regularly |
| `storage_data` | Uploaded files: receipts, exercise attachments, support ticket attachments | Yes — back up regularly |
| `public_assets` | Vite-built JS/CSS and public media | Auto-repopulated from the Docker image on each startup |

### Backup commands

```bash
# PostgreSQL dump
docker compose exec postgres pg_dump -U ${DB_USERNAME} ${DB_DATABASE} > backup_$(date +%Y%m%d).sql

# Storage files
tar -czf storage_backup_$(date +%Y%m%d).tar.gz \
  $(docker volume inspect animatorsho_storage_data --format '{{ .Mountpoint }}')
```

---

## Using an External Managed PostgreSQL Database

To connect to a managed PostgreSQL instance (e.g., Amazon RDS, DigitalOcean Managed DB, Supabase) instead of the bundled `postgres` service:

1. Remove or comment out the `postgres` service in `docker-compose.yml`
2. Remove `postgres` from `app.depends_on`
3. Set the following in `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=your-managed-host.example.com
DB_PORT=5432
DB_DATABASE=animatorsho
DB_USERNAME=animatorsho_app
DB_PASSWORD=your-password
DB_SSLMODE=require
```

4. Remove the override in `docker-compose.yml`'s `app.environment` block that forces `DB_HOST=postgres`

---

## Logs

```bash
# All services
docker compose logs -f

# Specific service
docker compose logs -f app
docker compose logs -f nginx
docker compose logs -f postgres

# Laravel application logs (inside container)
docker compose exec app tail -f storage/logs/laravel.log
```

---

## Useful Commands

```bash
# Check service status
docker compose ps

# Restart all services
docker compose restart

# Restart a single service
docker compose restart app

# Open a shell in the app container
docker compose exec app sh

# Run an artisan command
docker compose exec app php artisan about
docker compose exec app php artisan migrate:status
docker compose exec app php artisan queue:monitor

# Clear all caches (useful after config changes)
docker compose exec app php artisan optimize:clear

# Stop all services (data volumes are preserved)
docker compose down

# Stop and remove all volumes — DESTROYS ALL DATA — use only to reset a dev copy
# docker compose down -v
```

---

## SSL / HTTPS

This setup serves HTTP on the configured `NGINX_PORT`. For HTTPS in production:

- Place a **reverse proxy** (Nginx host, Caddy, or Traefik) in front that terminates TLS and forwards to this port
- Or replace the `nginx` service with a TLS-capable image (e.g., add a Certbot sidecar)
- Set `APP_URL=https://animatorsho.ir` and `FORCE_HTTPS=true` in `.env`

---

## Storage Symlink

The `docker/entrypoint.sh` creates `public/storage → storage/app/public` automatically on first startup. No manual `storage:link` call is needed unless the `public_assets` volume is wiped.

---

## Security Reminders

- Never commit `.env` to version control
- Set `APP_DEBUG=false` in production
- Use `ZARINPAL_SANDBOX=false` for live payments
- Store database passwords and API keys only in `.env`
- Rotate `APP_KEY` generates a new key; existing sessions and encrypted data will be invalidated — regenerate only on fresh installs

---

## Related Documentation

- [PostgreSQL deployment guide](postgresql-deploy.md)
- [Animatorsho MVP plan](animatorsho-mvp-plan.md) (if present)
- `.env.example` — annotated environment variable reference
