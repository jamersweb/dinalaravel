<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;

class MediaStorageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     * Ensure all required media directories exist
     */
    public function boot(): void
    {
        $directories = [
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

        foreach ($directories as $directory) {
            $path = storage_path('fwd_media/' . $directory);
            if (!is_dir($path)) {
                Storage::disk('fwd_media')->makeDirectory($directory);
            }
        }
    }
}
