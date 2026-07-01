<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    /**
     * Whitelist of allowed media types/folders
     */
    private const ALLOWED_TYPES = [
        'avatar',
        'attachments',
        'exercises',
        'foods',
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
    public function show(string $type, string $filename)
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

        // Build file path
        $filePath = $type . '/' . $filename;
        $disk = 'fwd_media';

        // Legacy support: some old DB rows store image names without extension.
        // If exact file is missing and filename has no extension, try prefix match.
        if (!Storage::disk($disk)->exists($filePath) && pathinfo($filename, PATHINFO_EXTENSION) === '') {
            $allFiles = Storage::disk($disk)->files($type);
            foreach ($allFiles as $candidate) {
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
        
        // Check if file exists
        if (!Storage::disk($disk)->exists($filePath)) {
            return response()->json([
                'status' => false,
                'message' => 'File not found'
            ], 404);
        }

        if ($disk !== 'fwd_media') {
            $mimeType = Storage::disk($disk)->mimeType($filePath) ?: 'application/octet-stream';
            $fileSize = Storage::disk($disk)->size($filePath);
            $stream = Storage::disk($disk)->readStream($filePath);

            return response()->stream(function () use ($stream) {
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }, 200, [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Cache-Control' => 'public, max-age=86400',
                'Accept-Ranges' => 'bytes',
            ]);
        }

        // Get full path
        $fullPath = Storage::disk($disk)->path($filePath);

        // Get MIME type
        $mimeType = Storage::disk($disk)->mimeType($filePath);
        if (!$mimeType) {
            $mimeType = File::mimeType($fullPath);
        }

        // Explicit Content-Length prevents "Connection closed while receiving data" on some clients
        $fileSize = filesize($fullPath);
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Cache-Control' => 'public, max-age=86400',
            'Accept-Ranges' => 'bytes',
        ];

        return response()->file($fullPath, $headers);
    }

    /**
     * Serve media files for CMS (web authenticated)
     * Same logic but accessible via web routes
     */
    public function showWeb(string $type, string $filename)
    {
        return $this->show($type, $filename);
    }
}
