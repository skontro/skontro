<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'register'],

    'allowed_methods' => ['*'],

    // Explicit origin — NOT '*'. A wildcard origin is rejected by browsers
    // when credentials are included, and would be a security smell besides.
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // Required so the browser sends and accepts the session + XSRF cookies.
    'supports_credentials' => true,
];
