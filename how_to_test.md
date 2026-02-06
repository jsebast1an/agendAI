# How to Test WhatsApp (Dev)

## Prerequisites

- Laravel app running on port 8000.
- A valid Meta WhatsApp Business app with a WABA phone number.
- Access to Meta App Dashboard to configure Webhooks.
- ngrok running and pointing to the local Laravel server.

## 1) Start local server

Example (PowerShell):

```
php artisan serve --host=127.0.0.1 --port=8000
```

## 2) Start ngrok

Example:

```
ngrok http 8000
```

Copy the HTTPS URL ngrok gives you.

## 3) Configure Meta Webhook

In Meta App Dashboard:

- Go to Webhooks.
- Under **WhatsApp Business Account**, set the Callback URL to:

```
https://<your-ngrok-subdomain>.ngrok-free.app/api/webhook/whatsapp
```
- Select product 'business whatsapp'
- Set the **Verify Token** to match your `.env` value `WABA_VERIFY_TOKEN`.
- Click **Verify and Save**.
- Subscribe to **messages** event.

## 4) Send a test message

- From your phone, send a WhatsApp message to your business number.
- You should receive the AI reply on your test number (`WABA_TEST_TO`) when running in local/dev.

## 5) If it does not respond

- Check Laravel logs for errors.
- Confirm ngrok URL is active and correct.
- Confirm Webhook verification token matches `.env`.

---

## Notes

- Webhook routes are defined in `routes/api.php`:
  - `GET /api/webhook/whatsapp` for verify
  - `POST /api/webhook/whatsapp` for inbound messages
- In dev, outbound replies can be forced to a test number via `.env` (`WABA_TEST_TO`).
