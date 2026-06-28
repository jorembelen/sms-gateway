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
