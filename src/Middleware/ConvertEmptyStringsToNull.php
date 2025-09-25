<?php

namespace Moggie\Middleware;

/**
 * Class ConvertEmptyStringsToNull
 *
 * @package \Moggie\Middleware
 */
class ConvertEmptyStringsToNull
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->convertInput($request);

        return $next($request);
    }

    protected function convertInput(Request $request): void
    {
        $input = $request->all();
        $converted = $this->convertArray($input);
        $request->replace($converted);
    }

    protected function convertArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($value) && $value === '') {
                $result[$key] = null;
            } elseif (is_array($value)) {
                $result[$key] = $this->convertArray($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
