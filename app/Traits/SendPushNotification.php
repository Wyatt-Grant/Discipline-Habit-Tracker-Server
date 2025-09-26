<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

trait SendPushNotification
{
    public function pushNotifications($title, $subtitle, $body, $APN, $device)
    {
        if ($APN) {
            if ($device == "iOS") {
                $apns_topic = 'dev.wyattgrant.DisciplineHabitTracker';
                $apns_pem = Storage::path('/certs/app_pushnotification_end.pem');
                $apns_pass = 'pass987';
                $body = array(
                    'aps' => array(
                        "alert" => [
                            "title" => $title,
                            "subtitle" => $subtitle,
                            "body" => $body
                        ],
                        'sound' => 'default'
                    ),
                );

                $ch = \curl_init("https://api.push.apple.com/3/device/$APN");
                // https://api.push.apple.com/3/device/ in production
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("apns-topic: " . $apns_topic));
                curl_setopt($ch, CURLOPT_SSLCERT, $apns_pem);
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $apns_pass);
                $result = curl_exec($ch);
                if ($result) {
                    Log::error($result);
                }
            } else if ($device = "Android") {
                try {
                    // Initialize the Firebase Admin SDK
                    $firebase = (new Factory)->withServiceAccount(Storage::path('/certs/discipline-9d02d-a6148735ef22.json'));

                    // Construct the message
                    $message = CloudMessage::withTarget('token', $APN)
                        ->withNotification(Notification::create($title . " - " . $subtitle, $body));

                    // Send the message
                    $firebase->createMessaging()->send($message);
                } catch (Exception $e) {
                    // do nothing, it's just a push notification
                }
            }
        }
    }
}
