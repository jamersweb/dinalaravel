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
  - User purchases via direct store IAP (`StoreSubscriptionController`)
  - User purchases via traditional payment (`PaymentsController`)

**Fix:** If the user has an active store subscription, verify the direct IAP receipt through the app and confirm the backend `store-subscription/verify` endpoint updated the subscription rows.

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

