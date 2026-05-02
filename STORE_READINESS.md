# Store Readiness – Backend Notes

For full app audit (Flutter, Laravel, Google Play, App Store), see:
**`../fitnesswithdina_mobile/APP_AUDIT_REPORT.md`**

## Backend-Specific Checklist

- [ ] Set `REVENUECAT_WEBHOOK_AUTH` in production `.env` (webhook rejects unauthenticated requests in production if not set)
- [ ] Use HTTPS for all API URLs in production
- [ ] Queue worker running for `ProcessRevenueCatWebhookJob` (use `database` or `redis` queue)
- [ ] Configure CORS for production app domain
- [ ] Verify Passport token expiry is appropriate (currently 4 weeks)
