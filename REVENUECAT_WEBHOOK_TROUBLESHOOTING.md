# RevenueCat Webhook Troubleshooting

If purchases work in the app (RevenueCat shows isPro) but the backend still returns "No Active Subscription", the webhook or its processing is likely failing.

## 1. Test Store Does NOT Send Webhooks

**Important:** RevenueCat's **Test Store** (SimulatedStoreBillingAbstract) simulates purchases locally. These **do not trigger real webhooks** to your server.

- To test webhooks: Use **RevenueCat Dashboard → Webhooks → Send Test Event**
- Or use real **App Store Sandbox** / **Google Play Test** (creates real webhook events)

**Note:** "Send Test Event" uses fake UUIDs. `store: "TEST_STORE"` (simulator) may also send `$RCAnonymousID` even after `logIn`—known RevenueCat quirk. The app now blocks the paywall until `identifyUser` succeeds. For reliable webhook testing, use **real App Store Sandbox** (not Test Store): sign out of App Store on device, then purchase—sandbox sends proper `app_user_id`.

## 2. Verify Webhook Is Being Called

### Enable debug logging (optional)

Add to `.env` to log the full webhook payload:

```
REVENUECAT_WEBHOOK_DEBUG=true
```

Then run `php artisan config:clear`. Disable when done debugging.

### Check Laravel logs

After a purchase (or sandbox purchase from simulator):

```bash
tail -f storage/logs/laravel.log
```

You should see:
```
RevenueCat webhook: Received {"app_user_id":"88","original_app_user_id":"88","aliases":["88"],"event_type":"INITIAL_PURCHASE","environment":"SANDBOX","store":"APP_STORE"}
```

- **app_user_id** – Must be your backend user ID (e.g. `"88"`), not UUID or `$RCAnonymousID:...`
- **aliases** – Should include the numeric user ID if you called `Purchases.logIn("88")` before purchase
- **environment** – `SANDBOX` for simulator, `PRODUCTION` for real purchases

If you see **nothing**, the webhook is not reaching your server.

### Common causes

| Issue | Fix |
|------|-----|
| Webhook not configured | RevenueCat Dashboard → Integrations → Webhooks → Add URL |
| Wrong URL | Use `https://srv1260934.hstgr.cloud/api/webhook/revenuecat` |
| Auth mismatch | `REVENUECAT_WEBHOOK_AUTH` in .env must match Authorization header in RevenueCat |
| 401/500 responses | Check `storage/logs/laravel.log` for errors |

## 3. app_user_id Must Be Backend User ID

The webhook uses `app_user_id` to find the user. It must be your Laravel `users.id` (e.g. `"88"`), **not** `$RCAnonymousID:xxx`.

### Verify in app

The app must call `Purchases.logIn("88")` **before** the user sees the paywall or makes a purchase. This is done in:
- `splash_screen.dart` (after login)
- `login_screen.dart` (after successful login)
- `revenuecat_subscription_screen.dart` (before paywall)

### Verify in webhook payload

When the webhook runs, check the log for `app_user_id`. If it shows `$RCAnonymousID:...`, the user was not identified before purchase.

**Fallback:** The backend also checks the `aliases` array (per RevenueCat docs). If `app_user_id` is anonymous but the user previously called `logIn("88")`, the numeric ID may appear in `aliases` and will be used. Look for log: `RevenueCat webhook: Resolved user from aliases`.

## 4. Database Requirements

### subscriptions table

The job needs a subscription with `access_type = 'full_access'` and `status = 1`:

```sql
SELECT id, name, access_type, status FROM subscriptions WHERE access_type = 'full_access' AND status = 1;
```

If this returns no rows, add one or fix existing subscriptions.

### user_details table

The job updates `user_details.subscription_status` and `subscription`. Ensure the user has a row:

```sql
SELECT user_id, subscription, subscription_status FROM user_details WHERE user_id = 88;
```

## 5. Queue Configuration

Default is `QUEUE_CONNECTION=sync` (runs jobs immediately). No queue worker needed.

If you use `database` or `redis`:

```bash
php artisan queue:work
```

Run this via Supervisor or similar so it stays running.

## 6. Manual Test

### Send Test Event from RevenueCat

1. RevenueCat Dashboard → Integrations → Webhooks
2. Click your webhook config
3. Use **Send Test Event**
4. Check `storage/logs/laravel.log` for "RevenueCat webhook: Received"

### Test with curl

```bash
curl -X POST https://srv1260934.hstgr.cloud/api/webhook/revenuecat \
  -H "Content-Type: application/json" \
  -H "Authorization: YOUR_REVENUECAT_WEBHOOK_AUTH_VALUE" \
  -d '{"event":{"type":"INITIAL_PURCHASE","app_user_id":"88","original_app_user_id":"88","expiration_at_ms":1893456000000,"purchased_at_ms":1731340800000}}'
```

Replace `YOUR_REVENUECAT_WEBHOOK_AUTH_VALUE` with your actual secret. Then check:

```sql
SELECT * FROM user_subs WHERE user_id = 88 ORDER BY id DESC LIMIT 1;
SELECT subscription, subscription_status FROM user_details WHERE user_id = 88;
```

## 7. Manual Grant (Testing / Webhook Mismatch)

If the webhook has wrong `app_user_id` (UUID, anonymous) and you need to test subscription features:

```bash
# Grant 12 months to user ID 88
php artisan subscription:grant 88

# Grant to user by email
php artisan subscription:grant user@example.com

# Grant 6 months
php artisan subscription:grant 88 --months=6
```

This updates `user_subs` and `user_details` so `subscribe-program` and other subscription-gated routes work.

## 8. Checklist

- [ ] `REVENUECAT_WEBHOOK_AUTH` set in production `.env`
- [ ] Webhook URL in RevenueCat: `https://srv1260934.hstgr.cloud/api/webhook/revenuecat`
- [ ] Same secret in RevenueCat Authorization header
- [ ] At least one `subscriptions` row with `access_type='full_access'` and `status=1`
- [ ] App calls `identifyUser(userId)` with backend user ID before paywall
- [ ] Not relying on Test Store for webhook testing (use Send Test Event or real sandbox)
