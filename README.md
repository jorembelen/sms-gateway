# Self-Hosted SMS Gateway — Laravel API (Stage 1)

Backend for a self-hosted SMS gateway, similar in concept to httpSMS. A Flutter
Android app will later consume this API and send real SMS through a phone's SIM
card, triggered by FCM data push notifications. **This repo is the Laravel side
only** and is fully testable via curl/Postman without any phone or Flutter app.

## Stack

- Laravel 12 (PHP 8.2+)
- MySQL
- Laravel queues (database driver)
- FCM HTTP v1 API for push (see [FCM integration](#fcm-integration))

## How it works

```
POST /messages/send  ──► creates "pending" message ──► dispatches SendSmsJob (queued)
                                                              │
                          SendSmsJob finds the active device  ▼
                          and sends a data-only FCM message to its fcm_token
                                                              │
   phone receives push, sends the SMS, then calls            ▼
POST /devices/{id}/callback  ──► updates message to sent / delivered / failed
```

## API

All routes are under `/api/v1` and require an `X-API-Key` header matching
`API_GATEWAY_KEY`. Missing/incorrect key → `401`.

| Method | Path | Purpose |
| ------ | ---- | ------- |
| POST | `/api/v1/devices/register` | Register/refresh a device by FCM token |
| POST | `/api/v1/messages/send` | Queue an SMS for delivery |
| POST | `/api/v1/devices/{id}/callback` | Device reports a message's status |

### Error shape

Errors return a consistent JSON body:

```json
{ "error": "Invalid or missing API key." }
```

Validation errors (`422`) additionally include a field-keyed `errors` object.

## Setup

### 1. Install dependencies

```bash
composer install
```

### 2. Environment

The repo ships with a working `.env` for local Laragon development. If starting
from scratch:

```bash
cp .env.example .env
php artisan key:generate
```

Then set at least these values in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms_gateway
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database

API_GATEWAY_KEY=local-dev-secret-change-me
FIREBASE_CREDENTIALS_PATH=storage/app/firebase/service-account.json
FIREBASE_PROJECT_ID=
```

### 3. MySQL database

Create the database referenced by `DB_DATABASE`:

```bash
mysql -uroot -e "CREATE DATABASE sms_gateway CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 4. Migrate

```bash
php artisan migrate
# or, for a clean slate:
php artisan migrate:fresh
```

This creates the `devices` and `messages` tables plus Laravel's `jobs` /
`failed_jobs` tables for the database queue.

### 5. Run the app + queue worker

The API needs **two** processes: the web server and a queue worker (so
`SendSmsJob` actually runs).

```bash
# terminal 1 — web server
php artisan serve

# terminal 2 — queue worker
php artisan queue:work
```

## FCM integration

We use the **FCM HTTP v1 API directly** (raw HTTPS calls), not
`kreait/firebase-php`. The SDK is not installable on PHP 8.4 — it pulls in
`lcobucci/jwt` ≥5.4 (requires `ext-sodium`) and a `firebase/php-jwt` version
blocked by a security advisory. The HTTP v1 flow is small and self-contained:
[`app/Services/Fcm/FcmService.php`](app/Services/Fcm/FcmService.php) builds a
service-account JWT, signs it with `openssl` (RS256), exchanges it for a
short-lived OAuth2 access token (cached ~1h), and POSTs a **data-only** message
so the Flutter app can process it while backgrounded/terminated.

To enable real sends:

1. In the Firebase console: **Project settings → Service accounts → Generate new
   private key**. Download the JSON.
2. Save it to `storage/app/firebase/service-account.json` (this path is
   gitignored — the key is never committed).
3. Ensure `FIREBASE_CREDENTIALS_PATH` points to it. `project_id` is read from the
   JSON automatically, or set `FIREBASE_PROJECT_ID` to override.

Without a credentials file the gateway still runs end-to-end — the send attempt
just fails and the message is marked `failed` (handy for testing the flow).

## Testing Stage 1 (No Flutter Required)

### Required .env values

```dotenv
# API authentication — must match the X-API-Key header in every request
API_GATEWAY_KEY=local-dev-secret-change-me

# MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sms_gateway
DB_USERNAME=root
DB_PASSWORD=

# Queue driver — must be database so jobs persist across restarts
QUEUE_CONNECTION=database

# Firebase — path to your service account JSON downloaded from the Firebase console.
# Without this file the gateway still runs; SendSmsJob will mark the message failed
# after 3 retries (~40 s total) instead of sending to FCM.
FIREBASE_CREDENTIALS_PATH=storage/app/firebase/service-account.json
```

### Step-by-step startup

**1. Run migrations** (creates `devices`, `messages`, `jobs`, `failed_jobs` tables):

```bash
php artisan migrate
```

**2. Terminal 1 — web server:**

```bash
php artisan serve
# Listening on http://127.0.0.1:8000
```

**3. Terminal 2 — queue worker** (`SendSmsJob` only runs while this is active):

```bash
php artisan queue:work
```

### Endpoint tests (run in order)

**Step 1 — Register a device:**

```bash
curl -s -X POST http://127.0.0.1:8000/api/v1/devices/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: local-dev-secret-change-me" \
  -d '{"fcm_token":"test-fcm-token-abc123"}'
```

Successful response (`200 OK`):

```json
{ "id": 1, "status": "active", "last_seen_at": "2026-06-28T08:22:28.000000Z" }
```

**Step 2 — Send a message** (this queues `SendSmsJob`):

```bash
curl -s -X POST http://127.0.0.1:8000/api/v1/messages/send \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: local-dev-secret-change-me" \
  -d '{"to":"+15551234567","content":"Hello from the SMS gateway"}'
```

Successful response (`201 Created`):

```json
{ "id": 1, "status": "pending", "to": "+15551234567", "content": "Hello from the SMS gateway" }
```

**Step 3 — Simulate the phone reporting delivery:**

Replace `1` with the `id` values returned by steps 1 and 2 if they differ.

```bash
curl -s -X POST http://127.0.0.1:8000/api/v1/devices/1/callback \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: local-dev-secret-change-me" \
  -d '{"message_id":1,"status":"delivered","failure_reason":null}'
```

Successful response (`200 OK`):

```json
{ "id": 1, "status": "delivered", "failure_reason": null, "to": "+15551234567" }
```

### Checking if SendSmsJob actually ran

After Step 2, the `queue:work` terminal (Terminal 2) prints immediately:

```
[2026-06-28 08:25:00] Processing: App\Jobs\SendSmsJob
[2026-06-28 08:25:00] Processed:  App\Jobs\SendSmsJob   ← success path (FCM accepted)
# or, if FCM fails:
[2026-06-28 08:25:00] Failed:     App\Jobs\SendSmsJob   ← after all 3 retries exhausted
```

| Where to look | What it tells you |
| ------------- | ----------------- |
| `queue:work` terminal | Live processing / retry / failure lines |
| `storage/logs/laravel.log` | `INFO SendSmsJob: FCM data message dispatched` (sent) or `WARNING SendSmsJob: transient FCM error` (retrying) |
| `failed_jobs` table | Populated only after all 3 retry attempts fail — `mysql -uroot sms_gateway -e "SELECT uuid,exception FROM failed_jobs\G"` |
| `messages` table | `status` = `pending` (FCM accepted, waiting for phone callback), `failed` (no device or FCM error after retries) |

**Without a real Firebase credentials file** the expected outcome is:
- `SendSmsJob` fires, finds the registered device, attempts FCM, throws `FcmException` (credentials file not found)
- The job retries twice more (10 s then 30 s backoff) → ~40 s total wait
- After 3 failures: message `status` → `failed`, `failure_reason` → `FCM send failed: Firebase credentials file not found at: …`
- A record appears in `failed_jobs`

### Auth / validation checks

```bash
# 401 — missing API key
curl -i -X POST http://127.0.0.1:8000/api/v1/devices/register \
  -H "Accept: application/json" -d '{}'

# 422 — invalid phone number + empty content
curl -X POST http://127.0.0.1:8000/api/v1/messages/send \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -H "X-API-Key: local-dev-secret-change-me" \
  -d '{"to":"abc","content":""}'
```

## Postman

Import both files from [`docs/`](docs/):

- `docs/sms-gateway.postman_collection.json` — the 3 endpoints with example bodies
- `docs/sms-gateway.postman_environment.json` — `base_url` + `api_key` variables

Select the **SMS Gateway (Local)** environment and the requests are plug-and-play.
The collection auto-captures `device_id` and `message_id` from responses so the
callback request works without manual editing.

## Admin Login (2FA)

The admin panel at `/admin` is protected by **password + SMS OTP two-factor authentication**.
OTP codes are delivered via this app's own SMS pipeline (the same `messages`/`SendSmsJob` flow
used for outbound messages), making this the first production use of that pipeline.

### How the flow works

```
/admin/login  — enter email + password
      │
      ▼  (credentials OK → OTP generated, SMS dispatched)
/admin/otp    — enter the 6-digit code from your phone
      │
      ▼  (code OK → Auth::login(), session regenerated)
/admin/       — dashboard
```

1. **Step 1 (`/admin/login`)** — submit email + password. On success a 6-digit code is
   generated cryptographically (`random_int`), stored **hashed** in `otp_codes`, and sent
   via SMS to the admin's registered phone number. You are placed in a pending state
   (`admin_otp_pending` session key) — you are NOT logged in yet.

2. **Step 2 (`/admin/otp`)** — submit the 6-digit code. The code must be:
   - entered within 5 minutes of issue
   - not previously consumed
   - within 5 attempts (locked after 5 wrong guesses — request a new code)

   On success the session is regenerated, `Auth::login()` is called, and you land on
   the dashboard. The OTP is marked `consumed_at` and cannot be reused.

3. **Rate limiting** — a maximum of 3 OTP SMS sends per user per 10-minute window.
   Hitting "Resend code" too many times will show a wait message.

### Seeding the admin user

Run the seeder to create (or re-create) the admin account:

```bash
php artisan db:seed --class=AdminUserSeeder
```

Default credentials seeded:

| Field    | Value                   |
|----------|-------------------------|
| Email    | `admin@smsgateway.local` |
| Password | `change-me-now`         |
| Phone    | `+639000000000` (placeholder — update before use) |

**Update the phone number before first login** — open Tinker or run a migration to set
the real number, otherwise OTP delivery will fail:

```bash
php artisan tinker
>>> \App\Models\User::where('email','admin@smsgateway.local')->update(['phone_number'=>'+639XXXXXXXXX']);
```

**Change the password** via Tinker as well:

```bash
>>> \App\Models\User::where('email','admin@smsgateway.local')->update(['password'=> bcrypt('your-new-password')]);
```

### Break-glass fallback (REQUIRED reading if your SMS device goes offline)

> **The OTP delivery depends on the single registered Android device being online and
> the gateway app being active.** If the device is off, out of battery, or the app was
> killed, no OTP SMS will arrive — and you could be locked out of the very dashboard
> you need to diagnose the problem.

**Break-glass procedure (Option A — artisan command, requires SSH/server access):**

This generates a valid OTP code directly in the database, bypassing SMS delivery entirely.
It uses exactly the same OTP verification code path — no special bypass logic.

```bash
# SSH to the server, then:
php artisan admin:bypass-otp
# or, if you have multiple users:
php artisan admin:bypass-otp --email=admin@smsgateway.local
```

Output:

```
  ⚠  BREAK-GLASS OTP — EMERGENCY USE ONLY

  User   : admin@smsgateway.local
  Code   : 482915
  Valid  : 15 minutes
  URL    : /admin/otp
```

**Procedure step-by-step:**

1. In your browser, go to `/admin/login` and enter your **password** as normal.
2. You land on `/admin/otp` (SMS never arrives).
3. SSH to the server and run `php artisan admin:bypass-otp`.
4. Copy the 6-digit code from the terminal output.
5. Enter it in the browser within 15 minutes.
6. You are logged in. Investigate why the SMS device is offline from the dashboard.

**Why Option A?** It requires server/SSH access — the same access you would need to
restart the app anyway. It leaves no plaintext credentials in log files (unlike a
logging-based fallback). The artisan command is not web-exposed. The generated code
goes through the same hash-verified OTP flow, so there is no separate code path to audit.

**Optional logging fallback (Option B — disabled by default):**

If you also want a log-file fallback for development or extreme emergencies, add to `.env`:

```dotenv
OTP_LOG_FALLBACK=true
```

When set, the plaintext OTP code is written to `storage/logs/laravel.log` at the INFO
level on each generation. **Leave this off in production.** It is opt-in and off by default.

### Admin panel pages

| URL | Component | Description |
| --- | --------- | ----------- |
| `/admin` | Dashboard | Stat cards (today's totals + success rate), 7-day volume chart, 10 most recent messages |
| `/admin/messages` | Messages | Full paginated table with live filters: phone search, status dropdown, date range |
| `/admin/devices` | Devices | Device table with FCM token display (truncated + copy button) |
| `/admin/failed` | Failed Messages | Failed-only view; failure reason always visible |

## Project layout

| Path | What |
| ---- | ---- |
| `app/Http/Middleware/VerifyApiKey.php` | `X-API-Key` check (timing-safe) |
| `app/Http/Controllers/Api/V1/` | Device + Message controllers |
| `app/Http/Requests/` | Form Request validation classes |
| `app/Models/Device.php`, `Message.php` | Eloquent models + relationships |
| `app/Jobs/SendSmsJob.php` | Queued FCM dispatch (3 tries, backoff) |
| `app/Services/Fcm/FcmService.php` | FCM HTTP v1 client |
| `routes/api.php` | `/api/v1` route definitions |
| `bootstrap/app.php` | Middleware alias + JSON error handlers |
| `database/migrations/` | `devices` + `messages` schema |


# run this from your local machine (Windows Git Bash or WSL)
cat "c:/laragon/www/sms/storage/app/firebase/service-account.json" | ssh -i ./dev-instance-key.pem ubuntu@3.250.47.173 "mkdir -p /var/www/sms/storage/app/firebase && cat > /var/www/sms/storage/app/firebase/service-account.json"

# Option 1 — write the file directly with sudo tee
cat "c:/laragon/www/sms/storage/app/firebase/service-account.json" | ssh -i ./dev-instance-key.pem ubuntu@3.250.47.173 "sudo mkdir -p /var/www/sms/storage/app/firebase && sudo tee /var/www/sms/storage/app/firebase/service-account.json > /dev/null"
