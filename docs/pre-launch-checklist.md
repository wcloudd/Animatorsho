# Pre-Launch QA Checklist & Deploy Readiness

Animatorsho — Laravel 13 + React/Inertia + TypeScript + Tailwind. Persian RTL, mobile-first.

**Purpose:** Review readiness before production deploy. This is documentation only — not a deploy runbook for today.

**Related docs:**
- [PostgreSQL Production Deployment](postgresql-deploy.md) — database, seeders, queue worker, first admin
- [Animatorsho MVP Plan](animatorsho-mvp-plan.md) — product scope
- `.env.example` — environment variable reference

---

## Quick status

| Area | Status | Notes |
|------|--------|-------|
| Automated tests | Run before deploy | `php artisan test --compact` |
| Frontend build | Run before deploy | `npm ci` + `npm run build` |
| PostgreSQL | **Deferred** | Local dev stays SQLite; production uses PostgreSQL per `postgresql-deploy.md` |
| Real integrations | **Pre-launch config required** | Zarinpal live, FarazSMS OTP pattern, SpotPlayer API, card-to-card details |
| Media / OG image | **Deferred** | Final photos/videos not uploaded; OG uses logo placeholder |
| Queue worker | **Recommended** | SMS sends synchronously today; `jobs` table exists for future/async work |

---

## 1. Environment variables

Copy `.env.example` → `.env` on the server. Never commit real secrets.

### Core application

| Variable | Local dev | Production |
|----------|-----------|------------|
| `APP_NAME` | `Laravel` or `انیماتورشو` | `انیماتورشو` (recommended) |
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `APP_KEY` | `php artisan key:generate` | Generate on fresh server |
| `APP_URL` | `http://localhost` | `https://animatorsho.ir` |
| `FORCE_HTTPS` | `false` | `true` |
| `TRUSTED_PROXIES` | `*` (default in local/testing) | Load-balancer IP(s) or `*` behind trusted reverse proxy |
| `LOG_LEVEL` | `debug` | `info` or `warning` |

### Database

| Variable | Local dev | Production |
|----------|-----------|------------|
| `DB_CONNECTION` | `sqlite` | `pgsql` (see [postgresql-deploy.md](postgresql-deploy.md)) |
| `DB_*` | SQLite file at `database/database.sqlite` | PostgreSQL host, port, database, user, password, `DB_SSLMODE` |

### Session, cache, queue

| Variable | Default | Notes |
|----------|---------|-------|
| `SESSION_DRIVER` | `database` | Requires migrations |
| `CACHE_STORE` | `database` | Requires migrations |
| `QUEUE_CONNECTION` | `database` | Requires migrations; run a queue worker if async jobs are added |

### Frontend build

| Variable | Notes |
|----------|-------|
| `VITE_APP_NAME` | Passed to Vite; rebuild after changing |

### Zarinpal

| Variable | Production |
|----------|------------|
| `ZARINPAL_MERCHANT_ID` | Live merchant ID |
| `ZARINPAL_SANDBOX` | `false` |

### Card-to-card manual payment

| Variable | Notes |
|----------|-------|
| `CARD_TO_CARD_NUMBER` | Shown at checkout — **required for card-to-card flow** |
| `CARD_TO_CARD_OWNER_NAME` | Account holder name — **required** |
| `CARD_TO_CARD_RECEIPT_MAX_KB` | Default `5120` |

### SMS / FarazSMS

| Variable | Production |
|----------|------------|
| `SMS_DRIVER` | `farazsms` |
| `SMS_ENABLED` | `true` |
| `SMS_ADMIN_MOBILE` | Admin alert destination |
| `FARAZSMS_API_KEY` | API key |
| `FARAZSMS_SENDER` | Sender line |
| `FARAZSMS_BASE_URL` | Default `https://api.iranpayamak.com/ws/v1` |

**OTP pattern (blocker if skipped):** Register the `otp_login` template with FarazSMS before launch:

> انیماتورشو: کد ورود شما {code} است.

Defined in `config/sms.php`. Admin panel can override SMS settings; env vars are the fallback.

### SpotPlayer

| Variable | Production |
|----------|------------|
| `SPOTPLAYER_ENABLED` | `true` |
| `SPOTPLAYER_API_BASE_URL` | Default `https://panel.spotplayer.ir` |
| `SPOTPLAYER_API_KEY` | Panel API key |
| `SPOTPLAYER_TIMEOUT` | Default `15` |
| `SPOTPLAYER_TEST_MODE` | `false` for live licenses |

### Mail (password reset via email)

| Variable | Production |
|----------|------------|
| `MAIL_MAILER` | `smtp` (or provider of choice) |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` | Real SMTP credentials |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Verified sender |

Local default: `MAIL_MAILER=log` (emails written to log, not delivered).

### Site settings (env fallbacks)

Managed primarily in **Admin → Site Settings**. Env fallbacks in `config/site.php`:

| Config key | Env override | Default |
|------------|--------------|---------|
| `purchases_enabled` | `PURCHASES_ENABLED` | `true` |
| `maintenance.title` | `MAINTENANCE_TITLE` | Persian maintenance title |
| `maintenance.message` | `MAINTENANCE_MESSAGE` | Persian maintenance message |

Maintenance mode toggle itself is stored in the database (`settings` table), not `.env`.

### Support attachments

| Variable | Default |
|----------|---------|
| `SUPPORT_ATTACHMENT_MAX_KB` | `5120` |

---

## 2. APP_URL and SEO / sitemap / robots

- Set `APP_URL` to the production canonical URL (`https://animatorsho.ir`).
- Set `FORCE_HTTPS=true` so generated URLs use HTTPS (`AppServiceProvider` + `config/app.php`).
- Public SEO routes:
  - `GET /robots.txt` → `seo.robots`
  - `GET /sitemap.xml` → `seo.sitemap`
- `SeoService` builds absolute URLs from `config('app.url')` — wrong `APP_URL` means wrong sitemap/robots/canonical/OG URLs.
- Sitemap includes: `/`, `/consultation`, `/checkout` only.
- `robots.txt` disallows: `/admin`, `/profile`, `/support`, `/settings`, auth/checkout callback paths.
- Private pages get `X-Robots-Tag: noindex` via `SetRobotsIndexingHeader` middleware.
- Default OG image: `/images/animatorsho-logo.svg` (placeholder until final media slice).

### Post-config verification

```powershell
# After setting APP_URL on production:
curl https://animatorsho.ir/robots.txt
curl https://animatorsho.ir/sitemap.xml
```

Confirm:
- Sitemap line points to production domain
- No `localhost` or `127.0.0.1` in output
- Sitemap does not list `/admin`, `/profile`, `/support`, `/login`

---

## 3. Database setup

| Environment | Driver | Notes |
|-------------|--------|-------|
| Local dev | SQLite (`database/database.sqlite`) | Do not use in production |
| Production | PostgreSQL 14+ | See [postgresql-deploy.md](postgresql-deploy.md) |

PHP extension required for production: `pdo_pgsql`.

**Safety:** `AppServiceProvider` blocks destructive Artisan commands (`migrate:fresh`, `db:wipe`, etc.) when `APP_ENV=production`.

---

## 4. Migrations and seeders

### Migrations (21 files)

Includes: users, cache, jobs, courses, packages, orders, payments, SpotPlayer licenses, SMS, settings, support tickets, OTP codes, consultation requests.

```powershell
php artisan migrate --force
```

### Seeders — use targeted classes only

| Seeder | Purpose | Required |
|--------|---------|----------|
| `AnimatorshoCourseSeeder` | Course catalog + package pricing | **Yes** |
| `SmsTemplateSeeder` | SMS template definitions | **Yes** (before enabling SMS) |

```powershell
php artisan db:seed --class=AnimatorshoCourseSeeder --force
php artisan db:seed --class=SmsTemplateSeeder --force
```

**Warning:** `php artisan db:seed` (no `--class`) runs `DatabaseSeeder`, which creates a test user (`test@example.com`). Do **not** run full seeder in production.

Both targeted seeders are idempotent — safe to re-run.

---

## 5. Storage and private uploads

```powershell
php artisan storage:link
```

| Upload type | Disk | Path prefix | Served via |
|-------------|------|-------------|------------|
| Card-to-card receipts | `local` (`storage/app/private`) | payment receipts | Admin payment receipt route |
| Support attachments | `local` (`storage/app/private`) | `support-attachments/` | Auth-gated download routes |

- Default `FILESYSTEM_DISK=local` — no S3 required for MVP.
- `storage:link` exposes `storage/app/public` only (user-visible public uploads if added later).
- Ensure web server user can write to `storage/` and `bootstrap/cache/`.
- Back up `storage/app/` with deployment backups.

---

## 6. Queue, cache, and session

| Component | Driver | Production action |
|-----------|--------|-------------------|
| Session | `database` | Migrations must run |
| Cache | `database` | Migrations must run |
| Queue | `database` | Migrations must run |

SMS currently sends **synchronously** (no `app/Jobs` classes). The `jobs` table exists for Laravel queue infrastructure.

If async jobs are added later, run a supervised worker:

```powershell
php artisan queue:work --sleep=3 --tries=3
```

Health check endpoint: `GET /up`

---

## 7. Mail and password reset

- Fortify features: registration + reset passwords (`config/fortify.php`).
- **Mobile OTP login:** `auth/mobile/*` routes.
- **Mobile password reset:** `password/mobile/*` routes (OTP-based).
- **Email password reset:** standard Fortify `/forgot-password` flow — requires working `MAIL_*` in production.

Rate limiters: login, OTP send/verify, password-reset OTP, consultation, support tickets.

---

## 8. SMS / FarazSMS

Pre-launch checklist:

- [ ] Register `otp_login` pattern with FarazSMS
- [ ] Set `SMS_DRIVER=farazsms`, `SMS_ENABLED=true`
- [ ] Set `FARAZSMS_API_KEY`, `FARAZSMS_SENDER`
- [ ] Set `SMS_ADMIN_MOBILE` for admin order/payment/ticket alerts
- [ ] Run `SmsTemplateSeeder` on production DB
- [ ] Review templates in Admin → SMS (enable/disable per template)
- [ ] Send one real OTP in staging before launch (not during `php artisan test`)

Tests force `SMS_DRIVER=fake` via `phpunit.xml` — automated tests never hit FarazSMS.

---

## 9. Zarinpal

Pre-launch checklist:

- [ ] Set live `ZARINPAL_MERCHANT_ID`
- [ ] Set `ZARINPAL_SANDBOX=false`
- [ ] Confirm callback URL resolves: `GET /checkout/zarinpal/callback`
- [ ] Run one small live payment in staging
- [ ] Verify server-side callback verification (already implemented)

---

## 10. Card-to-card config

Pre-launch checklist:

- [ ] Set `CARD_TO_CARD_NUMBER` and `CARD_TO_CARD_OWNER_NAME`
- [ ] Confirm checkout shows correct card details
- [ ] Test receipt upload (jpg/png/webp, max 5 MB default)
- [ ] Test admin approve → order paid → license provisioning path
- [ ] Test admin reject with note → user notification

---

## 11. SpotPlayer

Pre-launch checklist:

- [ ] Set `SPOTPLAYER_ENABLED=true`
- [ ] Set `SPOTPLAYER_API_KEY`
- [ ] Set `SPOTPLAYER_TEST_MODE=false` for production
- [ ] Confirm each package has SpotPlayer course ID in admin (seeded packages may need IDs)
- [ ] Test license activation after paid order
- [ ] Test admin retry-provision for failed licenses
- [ ] Verify license display in user profile

**Deferred:** Full end-to-end smoke against live SpotPlayer API on staging.

---

## 12. Installments

Configured in `config/installment.php` (not `.env`):

- Down payment: 40% of installment total
- Terms: 1-month (+500k toman), 2-month (+1M toman) surcharges
- Amounts snapshot on order creation — config changes do not affect existing orders

Pre-launch checklist:

- [ ] Test installment down payment (cash or card-to-card)
- [ ] Test admin approve installment request
- [ ] Test admin reject with note
- [ ] Confirm staged access behavior after approval

---

## 13. Admin account

No automated admin creation. After the real admin registers via mobile OTP:

```powershell
php artisan tinker --execute "App\Models\User::where('mobile', '09XXXXXXXXX')->update(['is_admin' => true]);"
```

Replace `09XXXXXXXXX` with the registered admin mobile.

Admin routes: `/admin/*` gated by `users.is_admin` + `EnsureUserIsAdmin` middleware.

---

## 14. Maintenance mode

Two layers:

1. **Database toggle** (Admin → Site Settings): `maintenance_mode_enabled` — shows custom maintenance page to non-admin visitors.
2. **Laravel maintenance** (`php artisan down` / `up`): file or cache driver via `APP_MAINTENANCE_DRIVER`.

Env fallbacks for maintenance copy: `MAINTENANCE_TITLE`, `MAINTENANCE_MESSAGE`.

Pre-launch checklist:

- [ ] Test maintenance mode on staging (toggle in admin)
- [ ] Confirm admin can still access `/admin`
- [ ] Confirm public pages show maintenance message

---

## 15. Purchase lock

- Env default: `PURCHASES_ENABLED=true` (`config/site.php`)
- Admin can disable purchases in Site Settings (stored in `settings` table)
- When disabled: checkout blocked, notice shown on landing/checkout

Pre-launch checklist:

- [ ] Test purchase lock toggle on staging
- [ ] Confirm checkout is blocked when disabled

---

## 16. Security checks

| Check | Status |
|-------|--------|
| `APP_DEBUG=false` in production | Required |
| `.env` gitignored | Yes |
| Destructive DB commands blocked in production | Yes (`AppServiceProvider`) |
| Auth on profile, orders, licenses, support, checkout POST | Yes |
| Users see only own data | Yes |
| Zarinpal callback verified server-side | Yes |
| Card-to-card / installment require admin approval | Yes |
| Payment secrets not in frontend | Yes |
| Rate limiting on login, OTP, consultation, support | Yes |
| Admin routes behind `admin` middleware | Yes |
| Trusted proxy headers for HTTPS behind load balancer | Configure `TRUSTED_PROXIES` |
| Password rules stricter in production (12+ chars, mixed case, symbols) | Yes (`AppServiceProvider`) |

---

## 17. Test and build commands

Run locally or in CI before every deploy:

```powershell
php artisan optimize:clear
vendor/bin/pint --dirty --format agent
php artisan test --compact
npm ci
npm run build
```

Production server (after code pull):

```powershell
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --class=AnimatorshoCourseSeeder --force
php artisan db:seed --class=SmsTemplateSeeder --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Fresh server only:

```powershell
php artisan key:generate
```

---

## 18. Manual smoke test checklist

Run on staging with production-like `.env` (except sandbox flags where noted).

### Auth

- [ ] Register with mobile OTP (`/register` → verify)
- [ ] Login with mobile + password (`/login`)
- [ ] Login with email + password (`/login/email`) if email set on account
- [ ] Password recovery via mobile OTP (`/password/mobile/*`)
- [ ] Password recovery via email (`/forgot-password`) — requires real mail config
- [ ] Mobile verification gate blocks checkout/consultation/support until verified

### Consultation

- [ ] Submit consultation form (auth + verified mobile required)
- [ ] Admin reviews/updates consultation in `/admin/consultations`

### Support

- [ ] Create support ticket with optional attachment
- [ ] User replies on ticket thread
- [ ] Admin replies from `/admin/support`
- [ ] Download attachment (user + admin)

### Checkout — cash (Zarinpal)

- [ ] Select package on landing → checkout
- [ ] Complete Zarinpal payment (sandbox first, then live)
- [ ] Land on `/checkout/result` with correct status
- [ ] Order appears in profile
- [ ] Retry online payment from profile (if applicable)

### Checkout — card-to-card

- [ ] Select card-to-card payment method
- [ ] Upload receipt
- [ ] Admin approves payment → order marked paid
- [ ] Admin rejects payment → user sees rejection note

### Checkout — installment

- [ ] Select comprehensive course + installment term
- [ ] Pay down payment (40%)
- [ ] Admin approves installment request
- [ ] Admin rejects installment request

### SpotPlayer

- [ ] License provisioned after paid order
- [ ] License visible in profile
- [ ] Admin retry-provision on failed license
- [ ] Admin manual activate/revoke

### Profile and navigation

- [ ] Profile page shows orders, licenses, settings
- [ ] Bottom nav: انیماتورشو / پشتیبان / پروفایل
- [ ] RTL layout correct at 390px width

### Admin

- [ ] Dashboard loads
- [ ] Packages edit (prices, SpotPlayer IDs)
- [ ] Orders, payments, installments, licenses
- [ ] SMS settings and template toggles
- [ ] Site settings: maintenance mode, purchase lock
- [ ] Support ticket inbox

### SEO

- [ ] `robots.txt` — correct disallow rules + sitemap URL with production `APP_URL`
- [ ] `sitemap.xml` — public URLs only, production domain
- [ ] Home page meta/OG props present (placeholder OG image OK)
- [ ] Admin/profile/support pages have `noindex` header

---

## 19. Post-deploy checks

- [ ] `GET /up` returns healthy
- [ ] Home page loads with built assets (no Vite manifest error)
- [ ] `robots.txt` and `sitemap.xml` use production URL
- [ ] Register + OTP works with FarazSMS
- [ ] One real Zarinpal payment completes
- [ ] Admin promoted and can log in
- [ ] `storage/` and `bootstrap/cache/` writable — no 500 on upload
- [ ] Error logs monitored (`storage/logs/laravel.log`)
- [ ] `APP_DEBUG=false` — generic error pages only
- [ ] SSL certificate valid, `FORCE_HTTPS=true`

---

## Known deferred items

Do not block launch documentation on these — track as follow-up slices:

| Item | Notes |
|------|-------|
| PostgreSQL switch | Deferred until after current edits; see [postgresql-deploy.md](postgresql-deploy.md) |
| Final media / photos / videos | Not uploaded to landing page yet |
| Dedicated OG image | Using `/images/animatorsho-logo.svg` placeholder |
| Real SpotPlayer API final smoke | Enable on staging before go-live |
| FarazSMS pattern OTP registration | Provider config step — register `otp_login` pattern |
| Discount codes | Not implemented |
| CourseAccess / Enrollment system | Not implemented (support category exists only) |
| Helper-admin / support-admin permissions | Single `is_admin` flag only — no role tiers |
| Async SMS queue | SMS sends synchronously today |
| S3 / cloud storage | Local disk only for MVP |

---

## Potential blockers before production

| Blocker | Severity | Action |
|---------|----------|--------|
| `APP_URL` not set to production domain | **High** | Wrong SEO URLs, Zarinpal callbacks, mail links |
| `ZARINPAL_SANDBOX=true` in production | **High** | No real payments |
| Missing `CARD_TO_CARD_*` env vars | **High** | Card-to-card checkout unusable |
| `SMS_ENABLED=false` or unregistered OTP pattern | **High** | Mobile login/register broken |
| `SPOTPLAYER_ENABLED=false` or missing API key | **High** | No license delivery after purchase |
| Full `db:seed` in production | **Medium** | Creates `test@example.com` user |
| No admin promoted | **High** | Cannot manage orders/payments |
| `MAIL_MAILER=log` in production | **Medium** | Email password reset does not deliver |
| No `storage:link` / permissions | **Medium** | Public storage broken if used |
| SpotPlayer course IDs missing on packages | **High** | License provisioning fails |
| Course/package prices not reviewed | **Medium** | Seeder defaults may not match launch pricing |

---

## File reference

| Area | Key paths |
|------|-----------|
| Env template | `.env.example` |
| Site / purchase lock | `config/site.php`, `app/Services/SiteSettingsService.php` |
| SEO | `config/seo.php`, `app/Services/SeoService.php`, `app/Http/Controllers/SeoController.php` |
| Zarinpal | `config/zarinpal.php` |
| Card-to-card | `config/card_to_card.php` |
| Installments | `config/installment.php` |
| SMS | `config/sms.php` |
| SpotPlayer | `config/spotplayer.php` |
| Storage | `config/filesystems.php`, `app/Services/PaymentReceiptStorageService.php` |
| Routes | `routes/web.php`, `routes/admin.php`, `routes/fortify.php`, `routes/password.php` |
| Tests | `tests/Feature/Seo/TechnicalSeoTest.php`, commerce/auth/admin test suites |

---

*Last updated: pre-launch QA slice — documentation only, no deploy performed.*
