<?php

require_once __DIR__ . '/src/Twilio/autoload.php';
require_once __DIR__ . '/helpers/config.local.php';

use Twilio\Rest\Client;

class SmsController {

   public static function sendSMS($numero, $message) {

        $sid = TWILIO_SID;
        $token = TWILIO_TOKEN;
        $twilioNumber = TWILIO_FROM;

        $client = new Client($sid, $token);

        try {

            $client->messages->create(
                $numero,
                [
                    "from" => $twilioNumber,
                    "body" => $message
                ]
            );

            return true;

        } catch (Exception $e) {

            return $e->getMessage();
       }
    }
}