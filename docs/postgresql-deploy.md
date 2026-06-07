# PostgreSQL Production Deployment

Animatorsho uses **SQLite** for local development and **PostgreSQL** for production.

This guide covers production setup, local data safety, and deployment commands. It does **not** implement automatic SQLite → PostgreSQL data migration.

---

## Overview

| Environment | Database | Config |
|-------------|----------|--------|
| Local dev | SQLite (`database/database.sqlite`) | `DB_CONNECTION=sqlite` |
| Production | PostgreSQL | `DB_CONNECTION=pgsql` |

The application schema is defined in Laravel migrations and is compatible with PostgreSQL. No driver-specific raw SQL is used in application code.

---

## Local SQLite Safety

Your local SQLite data lives in `database/database.sqlite`. Follow these rules to keep it safe:

1. **Backup first** before any database experiments:
   ```powershell
   copy database\database.sqlite database\database.sqlite.backup
   ```

2. **Do not run destructive commands** unless you intentionally want to reset:
   - `php artisan migrate:fresh`
   - `php artisan db:wipe`
   - `php artisan migrate:reset`

3. **Switching `DB_CONNECTION` only changes which database Laravel reads/writes.** Setting `DB_CONNECTION=pgsql` does not delete, overwrite, or modify `database/database.sqlite`. Both databases can coexist on your machine.

4. **Local dev should keep `DB_CONNECTION=sqlite`** in `.env`. Only production `.env` should use PostgreSQL.

5. **Moving data from SQLite to PostgreSQL is a separate step** — see [Data migration options](#data-migration-options) below.

---

## PostgreSQL Server Requirements

- PostgreSQL 14 or newer (14+ recommended)
- PHP 8.3 with `pdo_pgsql` extension enabled
- UTF-8 encoding
- Network access from the app server to the database host/port

Laravel connection settings are in `config/database.php` under the `pgsql` driver.

---

## Create Database and User

Run on the PostgreSQL server as a superuser. Replace placeholders with your own values — **do not commit real passwords**.

```sql
CREATE USER animatorsho_app WITH PASSWORD 'choose-a-strong-password';
CREATE DATABASE animatorsho OWNER animatorsho_app ENCODING 'UTF8';
GRANT ALL PRIVILEGES ON DATABASE animatorsho TO animatorsho_app;
```

On PostgreSQL 15+, you may also need:

```sql
\c animatorsho
GRANT ALL ON SCHEMA public TO animatorsho_app;
```

---

## Production `.env` Checklist

Copy `.env.example` to `.env` on the server and set these values.

### Core application

| Variable | Production value |
|----------|------------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Generate with `php artisan key:generate` on a fresh server |
| `APP_URL` | `https://animatorsho.ir` |
| `FORCE_HTTPS` | `true` |
| `TRUSTED_PROXIES` | Load-balancer IP(s) or `*` behind a trusted reverse proxy |
| `LOG_LEVEL` | `info` or `warning` (not `debug`) |

### Database (PostgreSQL)

| Variable | Example |
|----------|---------|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | Database host |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | `animatorsho` |
| `DB_USERNAME` | `animatorsho_app` |
| `DB_PASSWORD` | Strong password (never commit) |
| `DB_SSLMODE` | `prefer` (or `require` if your host requires SSL) |

### Session, cache, and queue

Default `.env.example` values work for a small deploy:

| Variable | Value |
|----------|-------|
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |

These require migrations to have run (included in the default migration set).

### Zarinpal

| Variable | Production value |
|----------|------------------|
| `ZARINPAL_MERCHANT_ID` | Your live merchant ID |
| `ZARINPAL_SANDBOX` | `false` |

### Card-to-card manual payment

| Variable | Notes |
|----------|-------|
| `CARD_TO_CARD_NUMBER` | Shown to users at checkout |
| `CARD_TO_CARD_OWNER_NAME` | Account holder name |
| `CARD_TO_CARD_RECEIPT_MAX_KB` | Default `5120` |

### SMS / FarazSMS

| Variable | Production value |
|----------|------------------|
| `SMS_DRIVER` | `farazsms` |
| `SMS_ENABLED` | `true` |
| `SMS_ADMIN_MOBILE` | Admin alert destination |
| `FARAZSMS_API_KEY` | FarazSMS API key |
| `FARAZSMS_SENDER` | Sender line |
| `FARAZSMS_BASE_URL` | Default `https://api.iranpayamak.com/ws/v1` |

#### FarazSMS OTP pattern (pre-launch checklist)

Mobile login sends OTP via the `otp_login` template defined in `config/sms.php`:

> انیماتورشو: کد ورود شما {code} است.

Before enabling production SMS, **register this message pattern with FarazSMS** (or your SMS panel). Unregistered OTP patterns may be rejected by the provider. This is a provider configuration step — no application code change is required for this slice.

SMS settings can also be overridden in the admin panel; environment variables serve as the fallback.

### Frontend build

| Variable | Notes |
|----------|-------|
| `VITE_APP_NAME` | Passed to Vite; run `npm run build` after changing |

---

## Deploy Commands

Run on the production server in order:

```powershell
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Fresh server only:
php artisan key:generate

php artisan migrate --force

php artisan db:seed --class=AnimatorshoCourseSeeder --force
php artisan db:seed --class=SmsTemplateSeeder --force

php artisan storage:link

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Targeted seeders only

| Seeder | Purpose |
|--------|---------|
| `AnimatorshoCourseSeeder` | Course catalog and package pricing (required) |
| `SmsTemplateSeeder` | SMS template definitions (required for SMS) |

### Warning: do not run full `DatabaseSeeder` in production

`php artisan db:seed` (without `--class`) runs `DatabaseSeeder`, which creates a **test user** (`test@example.com`). Use the targeted seeders above instead.

Both targeted seeders are idempotent (`updateOrCreate` / `firstOrCreate`) — safe to re-run.

---

## First Admin (safe promotion)

There is no automated admin-creation command. Promote a real user after they register via mobile OTP:

```powershell
php artisan tinker --execute "App\Models\User::where('mobile', '09XXXXXXXXX')->update(['is_admin' => true]);"
```

Replace `09XXXXXXXXX` with the registered admin mobile number.

Admin routes are gated by `users.is_admin` and the `EnsureUserIsAdmin` middleware.

---

## Storage and Private Files

```powershell
php artisan storage:link
```

This creates the public symlink for user-visible uploads.

Ensure the web server user can write to:

- `storage/` (logs, cache, sessions, uploaded files)
- `bootstrap/cache/` (compiled config/routes/views)

Support ticket attachments and card-to-card receipts are stored on the configured filesystem disk (`FILESYSTEM_DISK=local` by default). Back up `storage/app/` as part of your deployment backup strategy.

Never expose `.env`, `storage/` private paths, or API keys in the repository.

---

## Queue Worker

The app uses `QUEUE_CONNECTION=database`. A queue worker must run for queued jobs (SMS, notifications, etc.):

```powershell
php artisan queue:work --sleep=3 --tries=3
```

For production, configure a process supervisor (systemd, Supervisor, Laravel Forge daemon, etc.) to keep the worker running and restart it on failure.

---

## File Permissions and Cache

After deploy or `.env` changes:

```powershell
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Ensure `storage/` and `bootstrap/cache/` are writable by the PHP/web-server user.

Production safety: `AppServiceProvider` blocks destructive Artisan commands (`migrate:fresh`, `db:wipe`, etc.) when `APP_ENV=production`.

---

## Data Migration Options

This slice does **not** implement automatic SQLite → PostgreSQL migration. Choose one approach when needed:

### A. Fresh production (recommended for launch)

Run migrations and targeted seeders on an empty PostgreSQL database. Real users register and purchase on production from day one.

### B. Export/import selected data later

Export specific tables (users, orders, payments) from SQLite and import into PostgreSQL using manual SQL, CSV, or `pg_dump`-compatible tools. Requires careful handling of auto-increment IDs and foreign keys.

### C. Dedicated import script (future slice)

If local SQLite data must move to production PostgreSQL, build a one-time Artisan command or script in a separate approved slice. Do not run ad-hoc imports against production without a backup and dry run.

---

## Optional: Local PostgreSQL Smoke Test

If PostgreSQL is installed locally or available on a staging server, verify migrations against a **fresh empty database** (never production, never your SQLite file):

```powershell
# Use a separate .env or temporary env overrides — example:
# DB_CONNECTION=pgsql
# DB_DATABASE=animatorsho_test
# ...

php artisan migrate --force
php artisan test --compact
```

This is optional for local development. CI currently runs tests against SQLite in-memory (`phpunit.xml`).

---

## SMS and Automated Tests

Automated tests **never send real SMS**, even if your local `.env` configures FarazSMS.

`phpunit.xml` forces:

- `SMS_DRIVER=fake` — records SMS rows without HTTP
- `SMS_ENABLED=false` — tests that need SMS enable it explicitly in the database
- Empty `FARAZSMS_API_KEY` and `FARAZSMS_SENDER`

Feature tests also call `Http::preventStrayRequests()` so any accidental un-faked HTTP call fails the test instead of hitting a real provider.

**For manual real SMS testing**, temporarily set in `.env`:

```
SMS_DRIVER=farazsms
SMS_ENABLED=true
FARAZSMS_API_KEY=your-key
FARAZSMS_SENDER=your-sender
```

Then run a single manual flow or Tinker command. **Do not run `php artisan test`** while real FarazSMS credentials are active in `.env` — PHPUnit env overrides should protect you, but manual testing is safer one flow at a time.

---

## Security Reminders

- `.env` is gitignored — never commit credentials or API keys
- Set `APP_DEBUG=false` in production
- Use `ZARINPAL_SANDBOX=false` for live payments
- Register FarazSMS OTP pattern before enabling `SMS_ENABLED=true`
- Verify Zarinpal callbacks server-side (already implemented)
- Card-to-card and installment access require admin approval (already implemented)

---

## Related Documentation

- [Animatorsho MVP Plan](animatorsho-mvp-plan.md) — product scope and build order
- `.env.example` — environment variable reference with production comments
