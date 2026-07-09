<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    private const MEDIA_CACHE_TTL_SECONDS = 3600;

    /**
     * Whitelist of allowed media types/folders
     */
    private const ALLOWED_TYPES = [
        'avatar',
        'attachments',
        'exercises',
        'foods',
        'introduction',
        'meals',
        'meal_photos',
        'messages_files',
        'messages',
        'podcast',
        'posture',
        'product',
    ];

    /**
     * Serve media files securely
     * Only accessible to authenticated users (CMS or Mobile API)
     * 
     * @param string $type Media type (folder name)
     * @param string $filename Filename
     * @return BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, string $type, string $filename)
    {
        // Validate type is in whitelist
        if (!in_array($type, self::ALLOWED_TYPES)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid media type'
            ], 403);
        }

        // Prevent path traversal attacks
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid filename'
            ], 403);
        }

        $metadata = $this->resolveMediaMetadata($type, $filename);

        if ($metadata === null) {
            return response()->json([
                'status' => false,
                'message' => 'File not found'
            ], 404);
        }

        if ($this->matchesClientCache($request, $metadata['etag'], $metadata['last_modified'])) {
            return response('', 304, $this->buildCacheHeaders($metadata));
        }

        $disk = $metadata['disk'];
        $filePath = $metadata['file_path'];

        if ($disk !== 'fwd_media') {
            $stream = Storage::disk($disk)->readStream($filePath);

            return response()->stream(function () use ($stream) {
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }, 200, $this->buildCacheHeaders($metadata));
        }

        return response()->file($metadata['full_path'], $this->buildCacheHeaders($metadata));
    }

    /**
     * Returns the public API URL for the app introduction / guide video.
     */
    public function introductionVideoMeta()
    {
        $disk = 'fwd_media';
        $candidates = ['introduction/introduction.mp4', 'introduction.mp4'];

        $exists = false;
        foreach ($candidates as $candidate) {
            if (Storage::disk($disk)->exists($candidate)) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            return response()->json([
                'status' => false,
                'message' => 'Introduction video not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'url' => url('media/introduction/introduction.mp4'),
            ],
        ]);
    }

    /**
     * Serve media files for CMS (web authenticated)
     * Same logic but accessible via web routes
     */
    public function showWeb(Request $request, string $type, string $filename)
    {
        return $this->show($request, $type, $filename);
    }

    private function resolveMediaMetadata(string $type, string $filename): ?array
    {
        $cacheKey = sprintf('media:meta:%s:%s', $type, $filename);

        return Cache::remember($cacheKey, now()->addSeconds(self::MEDIA_CACHE_TTL_SECONDS), function () use ($type, $filename) {
            $filePath = $type . '/' . $filename;
            $disk = 'fwd_media';

            if ($type === 'introduction' && !Storage::disk($disk)->exists($filePath)) {
                $rootPath = $filename;
                if (Storage::disk($disk)->exists($rootPath)) {
                    $filePath = $rootPath;
                }
            }

            if (!Storage::disk($disk)->exists($filePath) && pathinfo($filename, PATHINFO_EXTENSION) === '') {
                foreach (Storage::disk($disk)->files($type) as $candidate) {
                    if (basename($candidate) === $filename || str_starts_with(basename($candidate), $filename . '.')) {
                        $filePath = $candidate;
                        break;
                    }
                }
            }

            $defaultDisk = config('filesystems.default');
            if (!Storage::disk($disk)->exists($filePath) && $defaultDisk !== $disk && Storage::disk($defaultDisk)->exists($filePath)) {
                $disk = $defaultDisk;
            }

            if (!Storage::disk($disk)->exists($filePath)) {
                return null;
            }

            if ($disk !== 'fwd_media') {
                $mimeType = Storage::disk($disk)->mimeType($filePath) ?: 'application/octet-stream';
                $fileSize = Storage::disk($disk)->size($filePath);
                $lastModified = (int) Storage::disk($disk)->lastModified($filePath);

                return [
                    'disk' => $disk,
                    'file_path' => $filePath,
                    'full_path' => null,
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                    'is_video' => str_starts_with((string) $mimeType, 'video/'),
                    'last_modified' => $lastModified,
                    'etag' => md5($disk.'|'.$filePath.'|'.$fileSize.'|'.$lastModified),
                ];
            }

            $fullPath = Storage::disk($disk)->path($filePath);
            $mimeType = Storage::disk($disk)->mimeType($filePath) ?: File::mimeType($fullPath) ?: 'application/octet-stream';
            $fileSize = filesize($fullPath);
            $lastModified = filemtime($fullPath) ?: time();

            return [
                'disk' => $disk,
                'file_path' => $filePath,
                'full_path' => $fullPath,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'is_video' => str_starts_with((string) $mimeType, 'video/'),
                'last_modified' => $lastModified,
                'etag' => md5($disk.'|'.$filePath.'|'.$fileSize.'|'.$lastModified),
            ];
        });
    }

    private function buildCacheHeaders(array $metadata): array
    {
        return [
            'Content-Type' => $metadata['mime_type'],
            'Content-Length' => $metadata['file_size'],
            'Cache-Control' => $metadata['is_video'] ? 'public, max-age=604800, stale-while-revalidate=86400' : 'public, max-age=86400, stale-while-revalidate=3600',
            'Accept-Ranges' => 'bytes',
            'ETag' => '"'.$metadata['etag'].'"',
            'Last-Modified' => gmdate('D, d M Y H:i:s', $metadata['last_modified']).' GMT',
        ];
    }

    private function matchesClientCache(Request $request, string $etag, int $lastModified): bool
    {
        $ifNoneMatch = $request->headers->get('If-None-Match');
        if ($ifNoneMatch !== null && trim($ifNoneMatch, '"') === $etag) {
            return true;
        }

        $ifModifiedSince = $request->headers->get('If-Modified-Since');
        if ($ifModifiedSince !== null) {
            $clientTimestamp = strtotime($ifModifiedSince);
            if ($clientTimestamp !== false && $clientTimestamp >= $lastModified) {
                return true;
            }
        }

        return false;
    }
}
