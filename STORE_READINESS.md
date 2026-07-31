# Store Readiness – Backend Notes

For full app audit (Flutter, Laravel, Google Play, App Store), see:
**`../fitnesswithdina_mobile/APP_AUDIT_REPORT.md`**

## Backend-Specific Checklist

- [ ] Use HTTPS for all API URLs in production
- [ ] Queue worker running only for queues required by active backend jobs, such as AI tagging
- [ ] Configure CORS for production app domain
- [ ] Verify Passport token expiry is appropriate (currently 4 weeks)
