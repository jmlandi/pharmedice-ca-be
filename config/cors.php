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

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],  // TEMPORÁRIO: permite todas as origens
    
    // 'allowed_origins' => [
    //     // Desenvolvimento local
    //     'http://localhost:3000',
    //     'http://localhost:3001',
    //     'http://localhost:8080',
    //     'http://127.0.0.1:3000',
    //     'http://127.0.0.1:8080',
    //     
    //     // Domínios de produção
    //     'https://cliente.pharmedice.com.br',
    //     'https://api.pharmedice.com.br',
    //     'https://api-pharmedice.marcoslandi.com',
    // ],

    'allowed_origins_patterns' => [
        // Permite subdomínios em desenvolvimento
        '#^http://localhost:\d+$#',
        '#^http://127\.0\.0\.1:\d+$#',
        '#^https://.*\.pharmedice\.com$#',
        '#^https://.*\.pharmedice\.com\.br$#',
        '#^https://.*\.marcoslandi\.com$#',
        // all pharmedice.com subdomains or paths
        
        // Descomente para permitir todos os subdomínios em produção
        // '#^https://.*\.meusite\.com$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Authorization', 'Content-Type'],

    'max_age' => 86400,

    'supports_credentials' => true,

];