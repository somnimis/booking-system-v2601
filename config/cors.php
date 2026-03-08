<?php

// config/cors.php (create if not exists)
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'admin/login'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('CORS_ALLOWED_ORIGINS', 'https://booking-system-v2601.onrender.com')],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];

