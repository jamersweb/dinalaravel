# Media Storage Migration: S3 to Local Storage

## Overview
This project has been migrated from S3 storage to local storage on the server. All media files are now stored in `storage/fwd_media/` and are accessible only through authenticated API endpoints.

## Storage Structure

All media files are stored under `storage/fwd_media/` with the following subdirectories:
- `avatar/` - User profile pictures
- `attachments/` - General attachments
- `exercises/` - Exercise videos and thumbnails
- `foods/` - Food images
- `meals/` - Meal images/videos and thumbnails
- `meal_photos/` - User-uploaded meal photos
- `messages_files/` - Chat message attachments
- `messages/` - Message files (alternative location)
- `podcast/` - Podcast audio files
- `posture/` - User posture pictures (front, back, side)
- `product/` - Product images

## Security

### Access Control
- **NO public access**: Files are NOT accessible via `/public` or `/storage` symlinks
- **Authentication required**: All media access requires authentication:
  - **Mobile API**: Requires `auth:api` middleware (Bearer token)
  - **CMS**: Requires `auth:api` + `checkAdmin` middleware

### Media Routes
- **API Route**: `GET /api/media/{type}/{filename}`
  - Protected by: `auth:api` middleware
  - Example: `/api/media/avatar/user123_profile_1234567890_abc123.jpg`
  
- **CMS Route**: `GET /cms/media/{type}/{filename}`
  - Protected by: `auth:api` + `checkAdmin` middleware
  - Example: `/cms/media/avatar/user123_profile_1234567890_abc123.jpg`

### Security Features
- **Path traversal protection**: Blocks `..`, `/`, `\` in filenames
- **Type whitelist**: Only allowed folder types can be accessed
- **Proper MIME types**: Returns correct Content-Type headers
- **Private caching**: Cache-Control headers set to private

## File Naming Convention

Files are stored with unique names to prevent collisions:
- Format: `{id}_{type}_{timestamp}_{uniqid}.{ext}`
- Example: `123_profile_1706123456_a1b2c3d4.jpg`

## URL Generation

### FileHandle Helper
The `FileHandle::getURL($file, $val)` helper generates secure URLs:
- `$file`: Filename (stored in database)
- `$val`: Folder type identifier:
  - `0` = avatar
  - `1` = posture
  - `2` = programs
  - `3` = messages_files
  - `4` = exercises
  - `5` = meals
  - `6` = attachments
  - `7` = product
  - `8` = podcast
  - `9` = foods

### Model Accessors
All model accessors now use `FileHandle::getURL()` to generate secure URLs:
- `UserDetail::getPictureAttribute()` - Avatar URLs
- `PosturePicture::getFrontPictureAttribute()` - Posture picture URLs
- `Meal::getFileAttribute()` - Meal media URLs
- `Exercise::getVideoUrlAttribute()` - Exercise video URLs
- `Message::getFileUrlAttribute()` - Message attachment URLs
- `Podcast::getFileAttribute()` - Podcast file URLs

## Configuration

### Filesystem Disk
A new disk `fwd_media` has been added to `config/filesystems.php`. On production, if your files live in a different path (e.g. under `public_html/storage/fwd_media`), set in `.env`:
```env
FWD_MEDIA_ROOT=/home/srv1260934.hstgr.cloud/public_html/storage/fwd_media
```
Example disk config (root is overridden by `FWD_MEDIA_ROOT` when set):
```php
'fwd_media' => [
    'driver' => 'local',
    'root' => env('FWD_MEDIA_ROOT') ?: storage_path('fwd_media'),
    'throw' => false,
],
```
Expected subfolders under that root: `foods/`, `meals/`, `meal_photos/`, `avatar/`, `exercises/`, etc.

### Image URLs (APP_URL)
Generated image URLs use Laravel’s `url()` helper, which uses `APP_URL` from `.env`. Set it to your public base URL so the app receives correct links, e.g.:
```env
APP_URL=https://srv1260934.hstgr.cloud
```
or your actual domain (e.g. `https://fwd.senarios.co`). Do not set `ASSET_URL` for API media; it is for public assets only.

### Service Provider
`MediaStorageServiceProvider` automatically creates required directories on boot.

## Upload Controllers Updated

All upload controllers now use the `fwd_media` disk:
- `UserInformationController` - Avatar and posture pictures
- `MealPhotosController` - Meal photos
- `MealsController` - Meal media files
- `ExerciseController` - Exercise videos and thumbnails
- `ChatsController` - Message attachments
- `PodcastsController` - Podcast files

## Backward Compatibility

- Existing S3 URLs in database will still work (if they start with `http://` or `https://`)
- New uploads will use local storage
- Old local storage paths (`/storage/...`) are automatically converted to secure URLs

## Testing Checklist

- [ ] Upload avatar → File saved in `storage/fwd_media/avatar/`
- [ ] Fetch user profile → Avatar URL points to `/api/media/avatar/...`
- [ ] Access media URL without auth → Returns 401/403
- [ ] Access media URL with valid auth → Returns file with correct Content-Type
- [ ] Attempt path traversal `/api/media/avatar/../../.env` → Blocked (404/403)
- [ ] Upload meal photo → File saved in `storage/fwd_media/meal_photos/`
- [ ] Upload exercise video → File saved in `storage/fwd_media/exercises/`
- [ ] Upload chat attachment → File saved in `storage/fwd_media/messages_files/`
- [ ] Upload podcast → File saved in `storage/fwd_media/podcast/`

## Deployment Notes

1. **Create directories**: Run `php artisan storage:link` (if needed) or ensure `storage/fwd_media/` exists
2. **Set permissions**: Ensure web server can write to `storage/fwd_media/`
3. **Clear cache**: Run `php artisan config:clear && php artisan cache:clear && php artisan route:clear`
4. **Verify**: Test media uploads and access after deployment

## Important Notes

- **Do NOT** place files in `public/` directory
- **Do NOT** create public symlinks for `fwd_media`
- **Ensure** proper file permissions on `storage/fwd_media/`
- **Monitor** disk space on server (local storage uses server disk)
- **Backup** `storage/fwd_media/` regularly
