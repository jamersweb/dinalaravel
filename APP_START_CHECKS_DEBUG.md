# app-start-checks API – Debug Guide

When you see `Que Answered: false` and `Subscription Active (backend): false`, the backend is returning what's in the database.

## Data sources

| Field | Source | Meaning |
|-------|--------|---------|
| `que_answered` | `user_answers` count for user | `true` if user has filled the consultation form |
| `subscription_active` | `user_details.subscription_status` | `true` if value is exactly `'active'` |

## Why `que_answered` is false

- No rows in `user_answers` for this user.
- User must complete the consultation form (Questions screen).
- Answers are stored via `QuestionsController` when the form is submitted.

**Fix:** Complete the consultation form in the app.

## Why `subscription_active` is false

- `user_details.subscription_status` is not `'active'` (it may be `null`, `'expired'`, etc.).
- `subscription_status` is set to `'active'` when:
  - User purchases via RevenueCat (webhook)
  - User purchases via traditional payment (PaymentsController)
  - Admin syncs subscription (sync-revenuecat-subscription endpoint)

**Fix:** If the user has an active subscription in RevenueCat:
1. Ensure the RevenueCat webhook is configured and receiving events.
2. Call the `sync-revenuecat-subscription` endpoint after login.

## Debugging

**Laravel log:** `app-start-checks` logs raw values for each request:

```
[log] app-start-checks: user_id=123, subscription_status_raw=null, user_answers_count=0, ...
```

**Database checks:**

```sql
-- Check user_details
SELECT user_id, subscription_status, subscription FROM user_details WHERE user_id = YOUR_USER_ID;

-- Check user_answers
SELECT COUNT(*) FROM user_answers WHERE user_id = YOUR_USER_ID;

-- Check user_subs (active subscriptions)
SELECT * FROM user_subs WHERE user_id = YOUR_USER_ID AND status = 'active';
```

## App behavior

The app uses **RevenueCat as the source of truth** when the backend lags:

```dart
final subscriptionActive = appStartCheckResponse?.data?.subscriptionActive == true || rcProvider.isPro;
```

So if the user has `isPro` in RevenueCat, they get access even when `subscription_active` is false from the backend. The backend is eventually updated by the webhook.
