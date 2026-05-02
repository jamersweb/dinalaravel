# RevenueCat Webhook Setup

The Laravel backend syncs subscription status from RevenueCat via webhooks. When a user purchases, renews, or cancels via RevenueCat (App Store / Google Play), the webhook updates the `user_subs` table so `full_access` checks reflect the current state.

## 1. Configure Environment

Add to your `.env`:

```env
# RevenueCat Webhook: secret for Authorization header (set same value in RevenueCat dashboard)
# Leave empty to disable auth (not recommended for production)
REVENUECAT_WEBHOOK_AUTH=your-secret-token-here
```

Use a strong, random string (e.g. `openssl rand -hex 32`). RevenueCat will send this value in the `Authorization` header with each webhook request.

## 2. Register Webhook in RevenueCat Dashboard

1. Log in to [RevenueCat Dashboard](https://app.revenuecat.com)
2. Select your project
3. Go to **Integrations** → **Webhooks**
4. Click **Add new configuration**
5. Configure:
   - **URL**: `https://your-domain.com/api/webhook/revenuecat`
     - For local testing: use [ngrok](https://ngrok.com) or similar to expose `http://127.0.0.1:8000`
   - **Authorization header**: Set to the same value as `REVENUECAT_WEBHOOK_AUTH` (e.g. `Bearer your-secret-token-here` or just `your-secret-token-here`)
   - **Events**: Send production, sandbox, or both
6. Save

## 3. Verify Flutter App User ID

The Flutter app must identify users with the Laravel user ID so webhooks can match:

```dart
Purchases.logIn(userId);  // userId = Laravel user id, e.g. "2"
```

This is already implemented in `RevenueCatProvider.identifyUser()` and called from login/splash with `profileProvider.profileDataModel?.userdata?.id.toString()`.

## 4. Flow Summary

| RevenueCat Event       | Backend Action                                             |
|-----------------------|------------------------------------------------------------|
| INITIAL_PURCHASE      | Create `user_subs` row with `status=active`, full_access sub |
| RENEWAL               | Same as above (replaces previous active sub)               |
| PRODUCT_CHANGE        | Same as above                                              |
| UNCANCELLATION        | Same as above                                              |
| EXPIRATION            | Set existing active `user_subs` to `status=expired`        |
| CANCELLATION          | Same as EXPIRATION                                         |
| BILLING_ISSUE         | Same as EXPIRATION                                         |

The `fullAceessMiddleware` checks for an active `UserSub` linked to a subscription with `access_type = 'full_access'`.

## 5. Queue (Production)

The webhook dispatches a job to process events. By default Laravel uses the `sync` driver (runs immediately). For production:

- Use `database` or `redis` queue
- Run `php artisan queue:work` (or use Laravel Horizon/Supervisor)
- Ensures quick 200 response to RevenueCat even under load

## 6. Local Testing

- Use ngrok: `ngrok http 8000` → use the HTTPS URL for the webhook
- Or use RevenueCat's **Test** event from the dashboard to send a sample webhook
