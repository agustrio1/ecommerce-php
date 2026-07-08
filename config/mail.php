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

    // Dipakai kalau MAIL_DRIVER=resend — lihat App\Core\Mail\Mailer.
    // Kirim via HTTP API (port 443), lebih kompatibel di shared hosting
    // yang sering memblokir port SMTP (587/465) keluar.
    'resend' => [
        'api_key'      => env('RESEND_API_KEY', ''),
        // Kalau kosong, Mailer otomatis fallback pakai 'from.address'/'from.name' di atas.
        'from_address' => env('RESEND_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', '')),
        'from_name'    => env('RESEND_FROM_NAME', env('MAIL_FROM_NAME', '')),
    ],
];