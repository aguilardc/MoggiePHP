<?php

namespace Moggie\Middleware;

use Moggie\Http\Request;
use Moggie\Http\Response;

/**
 * Class TrimStrings
 *
 * @package \Moggie\Middleware
 */
class TrimStrings
{
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
    ];

    public function handle(Request $request, \Closure $next): Response
    {
        $this->trimInput($request);

        return $next($request);
    }

    protected function trimInput(Request $request): void
    {
        $input = $request->all();
        $trimmed = $this->trimArray($input);
        $request->replace($trimmed);
    }

    protected function trimArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (in_array($key, $this->except, true)) {
                $result[$key] = $value;
            } elseif (is_string($value)) {
                $result[$key] = trim($value);
            } elseif (is_array($value)) {
                $result[$key] = $this->trimArray($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
