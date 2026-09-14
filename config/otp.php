<?php
return [
    'default_channel'=>env('OTP_DEFAULT_CHANNEL', 'mobile'),
    'default_region'=>(env('OTP_DEFAULT_REGION') ?: 'IN'),
    'supported_regions'=>array_values(array_filter(explode(',', env('OTP_SUPPORTED_REGIONS', '')))),
    'disclosure_version'=>'whatsapp-auth-v1',
    'default_country_code'=>env('OTP_DEFAULT_COUNTRY_CODE', '+91'), 'expiry_minutes'=>(int)env('OTP_EXPIRY_MINUTES', 5),
    'resend_seconds'=>(int)env('OTP_RESEND_SECONDS', 60), 'max_attempts'=>(int)env('OTP_MAX_ATTEMPTS', 5),
    'test_mode'=>filter_var(env('OTP_TEST_MODE', false), FILTER_VALIDATE_BOOL), 'test_code'=>env('OTP_TEST_CODE', '123456'),
    'driver'=>env('OTP_DRIVER', 'log'),
    'whatsapp'=>[
        'graph_version'=>env('META_WHATSAPP_GRAPH_VERSION', 'v23.0'),
        'phone_number_id'=>env('META_WHATSAPP_PHONE_NUMBER_ID'),
        'access_token'=>env('META_WHATSAPP_ACCESS_TOKEN'),
        'template'=>env('META_WHATSAPP_OTP_TEMPLATE'),
        'language'=>env('META_WHATSAPP_OTP_LANGUAGE', 'en_US'),
        'copy_code_button'=>filter_var(env('META_WHATSAPP_OTP_COPY_CODE_BUTTON', true), FILTER_VALIDATE_BOOL),
    ],
];
