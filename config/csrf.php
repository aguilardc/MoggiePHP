<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Cross-Site Request Forgery (CSRF) protection
    | for your application. CSRF tokens help protect your application from
    | cross-site request forgery attacks.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Lifetime
    |--------------------------------------------------------------------------
    |
    | Here you may specify the number of seconds that a CSRF token should be
    | considered valid. This will also determine how often the application
    | will rotate the CSRF token.
    |
    */

    'token_lifetime' => env('CSRF_TOKEN_LIFETIME', 7200), // 2 hours

    /*
    |--------------------------------------------------------------------------
    | CSRF Exception URIs
    |--------------------------------------------------------------------------
    |
    | Here you may specify URIs which should be excluded from CSRF verification.
    | These URIs are typically used for webhooks, API endpoints, or other
    | third-party integrations that cannot provide CSRF tokens.
    |
    */

    'except' => [
        '/webhooks/*',
        '/api/v1/public/*',
        '/health',
        '/metrics',
    ],

    /*
    |--------------------------------------------------------------------------
    | Verifiable HTTP Methods
    |--------------------------------------------------------------------------
    |
    | The HTTP methods that should be verified for CSRF tokens. GET, HEAD,
    | and OPTIONS requests are typically not verified as they should not
    | contain side effects.
    |
    */

    'verifiable_methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],

    /*
    |--------------------------------------------------------------------------
    | CSRF Token Headers
    |--------------------------------------------------------------------------
    |
    | The headers that should be checked for CSRF tokens. These are typically
    | sent by JavaScript frameworks and AJAX libraries.
    |
    */

    'token_headers' => [
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
        'HTTP_X_CSRF_TOKEN',
        'HTTP_X_XSRF_TOKEN',
    ],

    /*
    |--------------------------------------------------------------------------
    | Add Token to JSON Responses
    |--------------------------------------------------------------------------
    |
    | When this option is set to true, CSRF tokens will be automatically
    | added to JSON responses. This is useful for Single Page Applications
    | that need to access the current CSRF token.
    |
    */

    'add_to_response' => env('CSRF_ADD_TO_RESPONSE', false),

    /*
    |--------------------------------------------------------------------------
    | Add Token Cookie
    |--------------------------------------------------------------------------
    |
    | When this option is set to true, a CSRF token cookie will be added
    | to responses. This allows JavaScript frameworks to automatically
    | include the token in requests.
    |
    */

    'add_cookie' => env('CSRF_ADD_COOKIE', true),

    /*
    |--------------------------------------------------------------------------
    | CSRF Cookie Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the CSRF token cookie including name, domain, path,
    | security settings, and SameSite attribute.
    |
    */

    'cookie' => [
        'name' => env('CSRF_COOKIE_NAME', 'XSRF-TOKEN'),
        'domain' => env('CSRF_COOKIE_DOMAIN', null),
        'path' => env('CSRF_COOKIE_PATH', '/'),
        'secure' => env('CSRF_COOKIE_SECURE', false),
        'http_only' => env('CSRF_COOKIE_HTTP_ONLY', false),
        'same_site' => env('CSRF_COOKIE_SAME_SITE', 'lax'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Skip API Requests
    |--------------------------------------------------------------------------
    |
    | When this option is set to true, requests that appear to be API requests
    | (based on path prefix or Accept header) will be skipped for CSRF
    | verification. This is useful for APIs that use other authentication.
    |
    */

    'skip_api' => env('CSRF_SKIP_API', true),

    /*
    |--------------------------------------------------------------------------
    | API Path Prefixes
    |--------------------------------------------------------------------------
    |
    | The path prefixes that should be considered API requests and therefore
    | skipped for CSRF verification when 'skip_api' is enabled.
    |
    */

    'api_prefixes' => [
        'api/',
        '/api/',
        'api/v1/',
        '/api/v1/',
        'api/v2/',
        '/api/v2/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Token Generation
    |--------------------------------------------------------------------------
    |
    | You may specify a custom token generation strategy by providing a
    | callback. The callback should return a string token.
    |
    */

    'token_generator' => null, // Closure or null for default

    /*
    |--------------------------------------------------------------------------
    | Token Validation Strategy
    |--------------------------------------------------------------------------
    |
    | You may specify a custom token validation strategy. The callback
    | should accept two parameters (request token and session token)
    | and return a boolean.
    |
    */

    'token_validator' => null, // Closure or null for default

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configuration for how CSRF token mismatches should be handled,
    | including custom error messages and response formats.
    |
    */

    'error_handling' => [
        'json_message' => 'CSRF token mismatch',
        'json_status' => 419,
        'redirect_to' => null, // URL to redirect to on mismatch
        'show_token_in_error' => env('APP_DEBUG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuration for rate limiting CSRF token generation to prevent
    | abuse and potential DoS attacks.
    |
    */

    'rate_limiting' => [
        'enabled' => env('CSRF_RATE_LIMITING', false),
        'max_attempts' => env('CSRF_MAX_ATTEMPTS', 10),
        'decay_minutes' => env('CSRF_DECAY_MINUTES', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for logging CSRF-related events including token
    | mismatches, generation, and validation attempts.
    |
    */

    'logging' => [
        'log_mismatches' => env('CSRF_LOG_MISMATCHES', true),
        'log_generation' => env('CSRF_LOG_GENERATION', false),
        'log_validation' => env('CSRF_LOG_VALIDATION', false),
        'log_channel' => env('CSRF_LOG_CHANNEL', null),
    ],

];
