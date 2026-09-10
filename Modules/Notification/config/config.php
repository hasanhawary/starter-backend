<?php

return [
    'name' => 'Notification',

    'default_sms_driver' => env('DEFAULT_SMS_DRIVER', 'twilio'),
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
    ],
];
