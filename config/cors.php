<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | Paths that should have CORS enabled.
    | In local environment, enable CORS for all API routes.
    | In production, CORS is handled by Nginx (paths = []).
    |
    */
    'paths' => env('APP_ENV') === 'local' ? ['api/*', 'sanctum/csrf-cookie'] : [],

    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | In local environment (APP_ENV=local), all origins are allowed.
    | In production, only specific origins are allowed.
    |
    */
    'allowed_origins' => env('APP_ENV') === 'local' ? ['*'] : [
        // Domínios de produção
        'https://cliente.pharmedice.com.br',
        'https://api.pharmedice.com.br',
        'https://api-pharmedice.marcoslandi.com',
    ],

    'allowed_origins_patterns' => env('APP_ENV') === 'local' ? [] : [
        // Permite subdomínios em produção
        '#^https://.*\.pharmedice\.com$#',
        '#^https://.*\.pharmedice\.com\.br$#',
        '#^https://.*\.marcoslandi\.com$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization', 'Content-Type'],

    'max_age' => 86400,

    'supports_credentials' => true,

];