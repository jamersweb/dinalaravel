<?php

namespace App\Traits;

use App\Models\Notification;

trait NotificationsTrait {


    public function sendFirebaseNotification(array $recieverIds,$notiTitle,$notiContent) {

        $data = [
            "registration_ids" => $recieverIds,  // fcm_token/device_tokens of users to recieve noti
            "notification" => [
                "title" => $notiTitle,
                "body" => $notiContent,
            ]
        ];
        $dataString = json_encode($data);
        $SERVER_API_KEY = config('app.fcm_server');
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

        return $response;
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

        $data = [
            "registration_ids" => $recieverIds,  // fcm_token/device_tokens of users to recieve noti
            "notification" => [
                "title" => $notiTitle,
                "body" => $notiContent,
            ]
        ];
        $dataString = json_encode($data);
        $SERVER_API_KEY = config('app.fcm_server');
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

        return $response;
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
