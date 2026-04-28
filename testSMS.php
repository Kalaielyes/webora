<?php

require_once __DIR__ . '/controller/SmsController.php';

$result = SmsController::sendSMS(
    "+21653551049",
    "Test SMS Twilio 🚀"
);

var_dump($result);