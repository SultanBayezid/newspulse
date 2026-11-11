<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The Vue SPA runs on its own origin in development (Vite) and is served
    | by Nginx in production. Restrict allowed origins to the origins that
    | should talk to the API in production.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'feed', 'sitemap.xml'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', '*'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => false,
];
