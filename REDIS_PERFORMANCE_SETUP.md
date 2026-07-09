## Redis Performance Setup

This project already supports Redis for cache and queues. For production on a 4 vCPU / 8 GB RAM VPS, use Redis for cache first, then queues.

### Laravel `.env`

Set these values on the server:

```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_CONNECTION=default
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_PREFIX=dinafitness_
```

If you do not want sessions in Redis yet, keep `SESSION_DRIVER=file` and still use `CACHE_DRIVER=redis`.

### Laravel optimize commands

Run after updating `.env`:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue worker

Run a queue worker in production:

```bash
php artisan queue:work redis --queue=default --sleep=1 --tries=3 --timeout=120
```

Use Supervisor or systemd so the worker stays up.

### Redis server baseline

Recommended `redis.conf` direction for this VPS:

- `maxmemory 512mb` to `1024mb`
- `maxmemory-policy allkeys-lru`
- keep Redis bound to localhost or private network only
- enable persistence based on your backup policy

### What changed in code

- New media URLs now use `/media/...` instead of `/api/media/...`
- API limiter raised to `600/minute`
- Media limiter split out to `2400/minute`
- Media responses now send `ETag`, `Last-Modified`, and longer cache headers
- Meal list and discover endpoints now use Laravel cache, which benefits from Redis automatically when `CACHE_DRIVER=redis`
