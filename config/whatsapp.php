<?php

return [
    'webhook_verify_token' => env('META_WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    'app_secret' => env('META_APP_SECRET'),
    'portfolio_enabled' => env('WHATSAPP_PORTFOLIO_ENABLED', true),
    'support_enabled' => env('WHATSAPP_SUPPORT_ENABLED', false),
    'support_number' => env('WHATSAPP_SUPPORT_NUMBER'),
    'support_country_code' => (env('WHATSAPP_SUPPORT_COUNTRY_CODE') ?: '+91'),
    'support_message' => (env('WHATSAPP_SUPPORT_MESSAGE') ?: 'Hello, I need help with LensPic.'),
    'support_availability' => (env('WHATSAPP_SUPPORT_AVAILABILITY') ?: 'Contact our support team'),
];
