<?php

return [
    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'verify_service_sid' => env('TWILIO_VERIFY_SERVICE_SID'),
        'channel' => env('TWILIO_OTP_CHANNEL', 'sms'),
        'locale' => env('TWILIO_OTP_LOCALE', 'en'),
        'expiry_minutes' => (int) env('TWILIO_OTP_EXPIRY_MINUTES', 10),
        'resend_seconds' => max(60, (int) env('TWILIO_OTP_RESEND_SECONDS', 60)),
        'max_attempts' => max(1, min(5, (int) env('TWILIO_OTP_MAX_ATTEMPTS', 5))),
        'hourly_send_limit' => 5,
        'code_length' => 6,
    ],
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'gst_percent' => (float) env('RAZORPAY_GST_PERCENT', 18),
        'display_name' => env('RAZORPAY_DISPLAY_NAME', 'PraviTech'),
        'display_description' => env('RAZORPAY_DISPLAY_DESCRIPTION', 'LensPic Subscription'),
        'logo_url' => env('RAZORPAY_LOGO_URL'),
    ],

    'face_recognition' => [
        'url' => env('FACE_RECOGNITION_API_URL'),
        'match_path' => env('FACE_RECOGNITION_MATCH_PATH', '/recognize'),
        'timeout' => env('FACE_RECOGNITION_TIMEOUT', 90),
        'token' => env('FACE_RECOGNITION_API_TOKEN'),
        'photo_limit' => env('FACE_RECOGNITION_PHOTO_LIMIT', 0),
        'min_score' => env('FACE_RECOGNITION_MIN_SCORE', 0.35),
        'result_limit' => env('FACE_RECOGNITION_RESULT_LIMIT', 3),
        'port' => env('FACE_RECOGNITION_PORT', 8001),
        'health_path' => env('FACE_RECOGNITION_HEALTH_PATH', '/health'),
    ],
    'group_joining_tutorial' => [
        'url' => env('GROUP_JOINING_TUTORIAL_URL'),
        'thumbnail' => env('GROUP_JOINING_TUTORIAL_THUMBNAIL'),
        'duration' => env('GROUP_JOINING_TUTORIAL_DURATION', '1:30'),
        'title' => env('GROUP_JOINING_TUTORIAL_TITLE', 'How to join a photo group'),
    ],
];
