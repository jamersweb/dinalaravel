<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleTranslateService
{
    public function translate(string $text, string $source, string $target): string
    {
        $text = trim($text);
        if ($text === '' || strcasecmp($source, $target) === 0) {
            return $text;
        }

        $baseUrl = config('app.google_trans_baseUrl');
        $apiKey = config('app.google_api_key');
        if (empty($baseUrl) || empty($apiKey)) {
            Log::warning('GoogleTranslateService: missing google_trans_baseUrl or google_api_key');

            return $text;
        }

        $maxLen = 4500;
        if (strlen($text) > $maxLen) {
            $text = substr($text, 0, $maxLen);
        }

        try {
            $payload = [
                'q' => $text,
                'source' => $source,
                'target' => $target,
                'format' => 'text',
            ];

            $response = Http::post($baseUrl.'?key='.$apiKey, $payload)->json();
            if (isset($response['data']['translations'][0]['translatedText'])) {
                return $response['data']['translations'][0]['translatedText'];
            }
        } catch (Exception $e) {
            Log::info('GoogleTranslateService error: '.$e->getMessage());
        }

        return $text;
    }
}
