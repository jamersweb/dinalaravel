<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;

class FileHandle
{
    /**
     * Map of val parameter to folder name
     */
    private static $folderMap = [
        0 => 'avatar',
        1 => 'posture',
        2 => 'programs',
        3 => 'messages_files',
        4 => 'exercises',
        5 => 'meals',
        6 => 'attachments',
        7 => 'product',
        8 => 'podcast',
        9 => 'foods',
    ];

    /**
     * Generate secure local URL for media files
     * Returns URL pointing to protected media route instead of S3 presigned URL
     * 
     * @param string|null $file Filename (relative path)
     * @param int $val Folder type identifier (0=avatar, 1=posture, etc.)
     * @param bool $download Not used for local storage
     * @return string|null Secure media URL or null if file is empty
     */
    public static function getURL($file, $val = 0, $download = false)
    {
        // Return null if file is empty
        if (empty($file)) {
            return null;
        }

        // If already a full URL (http/https), return as-is (backward compatibility)
        if (strpos($file, "http://") !== false || strpos($file, "https://") !== false) {
            return $file;
        }

        // Clean up file path
        $file = str_replace('/storage/', '', $file);
        $file = str_replace('storage/', '', $file);
        
        // Remove folder prefix if already present (to avoid duplication)
        foreach (self::$folderMap as $folder) {
            if (strpos($file, $folder . '/') === 0) {
                $file = substr($file, strlen($folder) + 1);
                break;
            }
        }

        // Get folder name from val parameter
        $folder = self::$folderMap[$val] ?? 'others';
        
        // Build relative path: folder/filename
        $relativePath = $folder . '/' . $file;
        
        // Extract just the filename (last part after /)
        $filename = basename($file);
        
        // Serve via API route so media works even when /fwd_media symlink is missing on production.
        return url("/api/media/{$folder}/{$filename}");
    }
}
