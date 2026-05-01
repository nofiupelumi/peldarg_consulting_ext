<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'github' => [
        'pat' => env('GH_ARTIFACT_TOKEN'),
        'dispatch_repo' => env('GITHUB_DISPATCH_REPO', 'nofiupelumi/peldarg_consulting_ext'),
    ],

    'extractor' => [
        // Backward/forward compatible env var names.
        // GitHub Actions workflow uses CALLBACK_HMAC_SECRET + RESULT_UPLOAD_TOKEN.
        // Older deployments used EXTRACTOR_CALLBACK_SECRET + EXTRACTOR_BEARER_TOKEN.
        'secret' => env('CALLBACK_HMAC_SECRET', env('EXTRACTOR_CALLBACK_SECRET')),
        'token' => env('RESULT_UPLOAD_TOKEN', env('EXTRACTOR_BEARER_TOKEN')),
    ],

    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
        'callback_url' => env('PAYSTACK_CALLBACK_URL'),
        'webhook_url' => env('PAYSTACK_WEBHOOK_URL'),
    ],

    'partner' => [
        'token' => env('PARTNER_SHARED_TOKEN'),
        'authority_domain' => env('BILLING_AUTHORITY_DOMAIN', 'https://extract.peldargconsulting.com'),
        'allowed_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('PARTNER_ALLOWED_ORIGINS', 'https://extraction.riskcontrolnigeria.com'))))),
        'centralized_billing_enabled' => (bool) env('CENTRALIZED_BILLING_ENABLED', true),
    ],

    'contact_notify_to' => env('CONTACT_NOTIFY_TO'),

];
