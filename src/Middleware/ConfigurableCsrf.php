<?php

namespace Moggie\Middleware;

trait ConfigurableCsrf
{
    protected function csrfExcept(array $uris): array
    {
        return [
            'except' => $uris
        ];
    }

    protected function csrfConfig(array $config): array
    {
        return array_merge([
            'except' => [],
            'verifiable_methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
            'token_headers' => ['X-CSRF-TOKEN', 'X-XSRF-TOKEN'],
            'add_to_response' => false,
            'add_cookie' => true,
            'skip_api' => true,
            'api_prefixes' => ['api/', '/api/'],
            'token_lifetime' => 7200, // 2 hours
        ], $config);
    }
}
