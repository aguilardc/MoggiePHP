<?php

namespace Moggie\Middleware;

use Moggie\Http\Exceptions\UnauthorizedHttpException;

/**
 * Class Authenticate
 *
 * @package \Moggie\Middleware
 */
class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isAuthenticated($request)) {
            throw new UnauthorizedHttpException('Authentication required');
        }

        return $next($request);
    }

    protected function isAuthenticated(Request $request): bool
    {
        // Verificar token Bearer
        $token = $request->bearerToken();

        if (!$token) {
            return false;
        }

        // Aquí implementarías tu lógica de verificación de token
        // Por ejemplo, JWT validation
        return $this->validateToken($token);
    }

    protected function validateToken(string $token): bool
    {
        // Implementación básica - deberías usar JWT o similar
        return !empty($token) && strlen($token) > 10;
    }

}
