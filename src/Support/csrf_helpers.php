<?php

use Moggie\Middleware\VerifyCsrfToken;

if (!function_exists('csrf_token')) {
    /**
     * Get the current CSRF token
     */
    function csrf_token(): string
    {
        $request = app('request');
        $csrf = app(VerifyCsrfToken::class);

        return $csrf->getToken($request);
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token form field
     */
    function csrf_field(): string
    {
        $token = csrf_token();

        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_meta')) {
    /**
     * Generate CSRF token meta tags for HTML head
     */
    function csrf_meta(): string
    {
        $token = csrf_token();

        return '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_header')) {
    /**
     * Get the CSRF token header name
     */
    function csrf_header(): string
    {
        return 'X-CSRF-TOKEN';
    }
}

if (!function_exists('csrf_regenerate')) {
    /**
     * Regenerate the CSRF token
     */
    function csrf_regenerate(): string
    {
        $request = app('request');
        $csrf = app(VerifyCsrfToken::class);

        return $csrf->regenerateToken($request);
    }
}

if (!function_exists('csrf_validate')) {
    /**
     * Manually validate CSRF token
     */
    function csrf_validate(?string $token = null): bool
    {
        $request = app('request');
        $csrf = app(VerifyCsrfToken::class);

        if ($token === null) {
            // Try to get token from request
            $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');
        }

        if (!$token) {
            return false;
        }

        $sessionToken = $csrf->getToken($request);

        return hash_equals($sessionToken, $token);
    }
}

if (!function_exists('csrf_exempt')) {
    /**
     * Check if current request is exempt from CSRF verification
     */
    function csrf_exempt(): bool
    {
        $request = app('request');
        $config = config('csrf', []);
        $except = $config['except'] ?? [];

        $uri = $request->getPathInfo();

        foreach ($except as $pattern) {
            if ($pattern === $uri) {
                return true;
            }

            if (str_contains($pattern, '*')) {
                $regexPattern = str_replace('*', '.*', preg_quote($pattern, '/'));
                if (preg_match('/^' . $regexPattern . '$/', $uri)) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('csrf_javascript')) {
    /**
     * Generate JavaScript code to handle CSRF tokens
     */
    function csrf_javascript(): string
    {
        $token = csrf_token();

        return '<script>
            window.Laravel = window.Laravel || {};
            window.Laravel.csrfToken = "' . $token . '";

            // Setup CSRF token for AJAX requests
            if (typeof jQuery !== "undefined") {
                jQuery.ajaxSetup({
                    headers: {
                        "X-CSRF-TOKEN": "' . $token . '"
                    }
                });
            }

            // Setup CSRF token for Axios
            if (typeof axios !== "undefined") {
                axios.defaults.headers.common["X-CSRF-TOKEN"] = "' . $token . '";
            }

            // Setup CSRF token for Fetch API
            if (typeof fetch !== "undefined") {
                const originalFetch = window.fetch;
                window.fetch = function(url, options = {}) {
                    options.headers = options.headers || {};
                    if (!options.headers["X-CSRF-TOKEN"]) {
                        options.headers["X-CSRF-TOKEN"] = "' . $token . '";
                    }
                    return originalFetch(url, options);
                };
            }
        </script>';
    }
}

if (!function_exists('csrf_url')) {
    /**
     * Generate URL with CSRF token parameter
     */
    function csrf_url(string $url): string
    {
        $token = csrf_token();
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . '_token=' . urlencode($token);
    }
}

if (!function_exists('csrf_form')) {
    /**
     * Create a complete form with CSRF protection
     */
    function csrf_form(string $action, string $method = 'POST', array $attributes = []): string
    {
        $attributeString = '';
        foreach ($attributes as $key => $value) {
            $attributeString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }

        $form = '<form action="' . htmlspecialchars($action) . '" method="' . strtoupper($method) . '"' . $attributeString . '>';

        if (!in_array(strtoupper($method), ['GET', 'HEAD'])) {
            $form .= csrf_field();

            if (!in_array(strtoupper($method), ['GET', 'POST'])) {
                $form .= '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
            }
        }

        return $form;
    }
}

if (!function_exists('csrf_method_field')) {
    /**
     * Generate HTTP method field for forms
     */
    function csrf_method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . strtoupper($method) . '">';
    }
}

if (!function_exists('csrf_status')) {
    /**
     * Get CSRF protection status information
     */
    function csrf_status(): array
    {
        $request = app('request');
        $config = config('csrf', []);

        return [
            'enabled' => true,
            'token' => csrf_token(),
            'exempt' => csrf_exempt(),
            'method_requires_verification' => in_array($request->getMethod(), $config['verifiable_methods'] ?? []),
            'api_request' => $request->expectsJson(),
            'headers_checked' => $config['token_headers'] ?? [],
            'except_patterns' => $config['except'] ?? [],
        ];
    }
}

if (!function_exists('csrf_debug')) {
    /**
     * Debug CSRF token information (only in debug mode)
     */
    function csrf_debug(): ?array
    {
        if (!config('app.debug', false)) {
            return null;
        }

        $request = app('request');
        $token = csrf_token();

        return [
            'current_token' => $token,
            'request_token' => $request->input('_token'),
            'header_token' => $request->header('X-CSRF-TOKEN'),
            'tokens_match' => csrf_validate(),
            'method' => $request->getMethod(),
            'uri' => $request->getPathInfo(),
            'exempt' => csrf_exempt(),
            'config' => config('csrf'),
        ];
    }
}
