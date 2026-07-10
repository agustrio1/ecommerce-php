<?php

return [
    'driver'     => env('MAIL_DRIVER', 'smtp'),
    'host'       => env('MAIL_HOST', 'smtp.gmail.com'),
    'port'       => env('MAIL_PORT', 587),
    'encryption' => env('MAIL_ENCRYPTION', 'tls'),
    'username'   => env('MAIL_USERNAME', ''),
    'password'   => env('MAIL_PASSWORD', ''),
    'from'       => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@ecommerce.test'),
        'name'    => env('MAIL_FROM_NAME', 'Ecommerce PHP'),
    ],
    'resend' => [
        'api_key'      => env('RESEND_API_KEY', ''),
        'from_address' => env('RESEND_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', '')),
        'from_name'    => env('RESEND_FROM_NAME', env('MAIL_FROM_NAME', '')),
    ],
];