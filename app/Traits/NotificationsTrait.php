<?php

namespace App\Traits;

use App\Models\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait NotificationsTrait {


    public function sendFirebaseNotification(array $recieverIds,$notiTitle,$notiContent) {
        return self::sendFirebaseNotificationStatic($recieverIds, $notiTitle, $notiContent);
    }

    function storeNotification($reciever,$title,$title_ar,$content,$content_ar,$source=null){
        $noti = new Notification();
        $noti->reciever = $reciever;
        $noti->title = $title;
        $noti->title_ar = $title_ar;
        $noti->content = $content;
        $noti->content_ar = $content_ar;
        $noti->source = $source;
        $noti->save();
        return;
    }

    static function sendFirebaseNotificationStatic(array $recieverIds,$notiTitle,$notiContent) {
        $recieverIds = self::validFcmTokens($recieverIds);

        if (empty($recieverIds)) {
            return null;
        }

        if (self::hasFirebaseV1Config()) {
            return self::sendFirebaseNotificationV1($recieverIds, $notiTitle, $notiContent);
        }

        return self::sendFirebaseNotificationLegacy($recieverIds, $notiTitle, $notiContent);
    }

    private static function validFcmTokens(array $recieverIds): array
    {
        return array_values(array_filter(array_unique($recieverIds), function ($token) {
            return is_string($token) && trim($token) !== '';
        }));
    }

    private static function hasFirebaseV1Config(): bool
    {
        return !empty(config('services.firebase.project_id'))
            && !empty(config('services.firebase.service_account_json'));
    }

    private static function sendFirebaseNotificationLegacy(array $recieverIds, $notiTitle, $notiContent) {
        $SERVER_API_KEY = config('app.fcm_server');
        if (empty($SERVER_API_KEY)) {
            Log::warning('FCM notification skipped because Firebase credentials are missing.');
            return null;
        }

        $data = [
            "registration_ids" => $recieverIds,  // fcm_token/device_tokens of users to recieve noti
            "notification" => [
                "title" => $notiTitle,
                "body" => $notiContent,
            ]
        ];
        $dataString = json_encode($data);
        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $status >= 400) {
            Log::warning('FCM notification send failed.', [
                'status' => $status,
                'error' => $error,
                'response' => $response,
            ]);
        }

        return $response;
    }

    private static function sendFirebaseNotificationV1(array $recieverIds, $notiTitle, $notiContent): array
    {
        $accessToken = self::firebaseAccessToken();
        $projectId = config('services.firebase.project_id');
        if (empty($accessToken) || empty($projectId)) {
            Log::warning('FCM HTTP v1 notification skipped because Firebase credentials are invalid.');
            return [];
        }

        $responses = [];
        foreach ($recieverIds as $token) {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $notiTitle,
                            'body' => $notiContent,
                        ],
                    ],
                ]);

            $responses[] = [
                'status' => $response->status(),
                'body' => $response->body(),
            ];

            if (!$response->successful()) {
                Log::warning('FCM HTTP v1 notification send failed.', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        }

        return $responses;
    }

    private static function firebaseAccessToken(): ?string
    {
        $serviceAccount = self::firebaseServiceAccount();
        if (!is_array($serviceAccount) || empty($serviceAccount['client_email']) || empty($serviceAccount['private_key'])) {
            return null;
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];
        $unsigned = self::fcmBase64UrlEncode(json_encode($header)) . '.' . self::fcmBase64UrlEncode(json_encode($claims));
        if (!openssl_sign($unsigned, $signature, $serviceAccount['private_key'], 'sha256WithRSAEncryption')) {
            return null;
        }

        $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $unsigned . '.' . self::fcmBase64UrlEncode($signature),
        ]);

        return $response->ok() ? ($response->json('access_token') ?: null) : null;
    }

    private static function firebaseServiceAccount(): ?array
    {
        $raw = config('services.firebase.service_account_json');
        if (!$raw) return null;

        $serviceAccount = json_decode($raw, true);
        if (!is_array($serviceAccount) && is_file($raw)) {
            $serviceAccount = json_decode(file_get_contents($raw), true);
        }

        return is_array($serviceAccount) ? $serviceAccount : null;
    }

    private static function fcmBase64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    static function storeNotificationStatic($reciever,$title,$title_ar,$content,$content_ar,$source=null){
        $noti = new Notification();
        $noti->reciever = $reciever;
        $noti->title = $title;
        $noti->title_ar = $title_ar;
        $noti->content = $content;
        $noti->content_ar = $content_ar;
        $noti->source = $source;
        $noti->save();
        return;
    }
}
