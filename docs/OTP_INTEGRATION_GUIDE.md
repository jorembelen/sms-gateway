# OTP Integration Guide — SMS Gateway API

This guide shows how to send OTP (One-Time Password) messages from any external application using the SMS Gateway API.

---

## Overview

The SMS Gateway receives an API call, queues the message, and pushes it to a registered Android device via Firebase Cloud Messaging (FCM). The device's background service then sends the SMS through its physical SIM card.

```
Your App  ──POST /messages/send──▶  SMS Gateway  ──FCM push──▶  Android Device  ──SMS──▶  End User
```

---

## Base URL

```
http://<your-server-domain>/api/v1
```

Replace `<your-server-domain>` with your actual server address (e.g., `sms.yourdomain.com` or `192.168.1.100`).

---

## Authentication

Every request must include an `X-API-Key` header. Get the key from the gateway's `.env` file (`API_GATEWAY_KEY`).

```
X-API-Key: your-api-key-here
```

A missing or wrong key returns:

```json
HTTP 401 Unauthorized
{ "error": "Invalid or missing API key." }
```

---

## Send an OTP — `POST /api/v1/messages/send`

This is the only endpoint you need for sending OTPs.

### Request

| Header | Value |
|--------|-------|
| `Content-Type` | `application/json` |
| `X-API-Key` | your API key |

**Body:**

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `to` | string | Yes | Phone number — optional leading `+`, 7–15 digits (E.164 format recommended) |
| `content` | string | Yes | Message body, max 320 characters |

**Example:**
```json
{
  "to": "+639171234567",
  "content": "Your OTP is: 482913. It expires in 5 minutes. Do not share this with anyone."
}
```

### Response — `201 Created`

```json
{
  "id": 42,
  "status": "pending",
  "to": "+639171234567",
  "content": "Your OTP is: 482913. It expires in 5 minutes. Do not share this with anyone."
}
```

| Field | Description |
|-------|-------------|
| `id` | Message ID — save this if you want to track delivery status |
| `status` | Always `pending` on creation; updated asynchronously |
| `to` | Recipient phone number as stored |
| `content` | Message body as stored |

---

## Error Responses

| Status | Cause | Body |
|--------|-------|------|
| `401` | Missing or invalid `X-API-Key` | `{ "error": "Invalid or missing API key." }` |
| `422` | Validation failed | See below |
| `500` | Server error | `{ "error": "..." }` |

**Validation error example:**
```json
HTTP 422 Unprocessable Entity
{
  "message": "The given data was invalid.",
  "errors": {
    "to": ["The \"to\" field must be a valid phone number (7-15 digits, optional leading +)."],
    "content": ["The content field is required."]
  }
}
```

---

## Code Examples

### PHP (cURL)

```php
function sendOtp(string $phone, string $otp): array
{
    $payload = json_encode([
        'to'      => $phone,
        'content' => "Your OTP is: {$otp}. It expires in 5 minutes.",
    ]);

    $ch = curl_init('http://<your-server>/api/v1/messages/send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-API-Key: your-api-key-here',
        ],
    ]);

    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status !== 201) {
        throw new RuntimeException("SMS gateway error {$status}: {$response}");
    }

    return json_decode($response, true);
}

// Usage
$result = sendOtp('+639171234567', '482913');
echo "Message queued with ID: " . $result['id'];
```

### PHP (Laravel HTTP Client)

```php
use Illuminate\Support\Facades\Http;

function sendOtp(string $phone, string $otp): array
{
    $response = Http::withHeaders([
        'X-API-Key' => config('services.sms_gateway.api_key'),
    ])->post(config('services.sms_gateway.url') . '/api/v1/messages/send', [
        'to'      => $phone,
        'content' => "Your OTP is: {$otp}. It expires in 5 minutes.",
    ]);

    $response->throw(); // Throws on 4xx/5xx

    return $response->json();
}
```

Add to your `config/services.php`:
```php
'sms_gateway' => [
    'url'     => env('SMS_GATEWAY_URL'),
    'api_key' => env('SMS_GATEWAY_API_KEY'),
],
```

### JavaScript / Node.js (fetch)

```js
async function sendOtp(phone, otp) {
  const response = await fetch('http://<your-server>/api/v1/messages/send', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': process.env.SMS_GATEWAY_API_KEY,
    },
    body: JSON.stringify({
      to: phone,
      content: `Your OTP is: ${otp}. It expires in 5 minutes.`,
    }),
  });

  if (!response.ok) {
    const err = await response.json();
    throw new Error(`SMS gateway error ${response.status}: ${JSON.stringify(err)}`);
  }

  return response.json();
}

// Usage
const result = await sendOtp('+639171234567', '482913');
console.log('Message queued with ID:', result.id);
```

### Python (requests)

```python
import requests
import os

def send_otp(phone: str, otp: str) -> dict:
    response = requests.post(
        'http://<your-server>/api/v1/messages/send',
        json={
            'to': phone,
            'content': f'Your OTP is: {otp}. It expires in 5 minutes.',
        },
        headers={
            'X-API-Key': os.environ['SMS_GATEWAY_API_KEY'],
        },
        timeout=10,
    )
    response.raise_for_status()
    return response.json()

# Usage
result = send_otp('+639171234567', '482913')
print(f"Message queued with ID: {result['id']}")
```

### Dart / Flutter

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

Future<Map<String, dynamic>> sendOtp(String phone, String otp) async {
  final response = await http.post(
    Uri.parse('http://<your-server>/api/v1/messages/send'),
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': const String.fromEnvironment('SMS_GATEWAY_API_KEY'),
    },
    body: jsonEncode({
      'to': phone,
      'content': 'Your OTP is: $otp. It expires in 5 minutes.',
    }),
  );

  if (response.statusCode != 201) {
    throw Exception('SMS gateway error ${response.statusCode}: ${response.body}');
  }

  return jsonDecode(response.body) as Map<String, dynamic>;
}
```

---

## Phone Number Format

The gateway accepts numbers in E.164 format (recommended) or bare digits:

| Format | Example | Valid? |
|--------|---------|--------|
| E.164 with country code | `+639171234567` | Yes |
| Without `+` | `639171234567` | Yes |
| Local format (7-15 digits) | `09171234567` | Yes |
| Too short (< 7 digits) | `12345` | No — rejected with 422 |
| Too long (> 15 digits) | `123456789012345678` | No — rejected with 422 |

---

## Delivery Flow & Status

After you receive a `201` response, the message goes through these states asynchronously:

```
pending  →  sent  →  delivered
                ↘  failed
```

| Status | Meaning |
|--------|---------|
| `pending` | Queued, FCM push not yet sent |
| `sent` | Android device received it and submitted the SMS |
| `delivered` | Carrier confirmed delivery (device-reported) |
| `failed` | Could not be sent; `failure_reason` explains why |

The status is updated by the Android device calling the gateway's callback endpoint — your app does not need to implement anything for this.

---

## Checking Message Status (Optional)

There is currently no polling endpoint. If you need delivery confirmation, implement a webhook receiver in your app and contact the gateway administrator to configure the callback URL, or track status out-of-band.

---

## Prerequisites

Before your integration will work, ensure:

1. **The gateway is running** — Laravel app served (e.g., via Apache/Nginx on Laragon).
2. **Queue worker is active** — Run `php artisan queue:work` on the server, or configure it as a system service.
3. **An Android device is registered** — The companion Android app must be installed, logged in, and have registered its FCM token with `POST /api/v1/devices/register`.
4. **Firebase is configured** — `storage/app/firebase/service-account.json` must exist on the server.

---

## Quick Test (cURL)

```bash
curl -X POST http://<your-server>/api/v1/messages/send \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-api-key-here" \
  -d '{"to":"+639171234567","content":"Test OTP: 123456"}'
```

Expected response:
```json
{
  "id": 1,
  "status": "pending",
  "to": "+639171234567",
  "content": "Test OTP: 123456"
}
```
