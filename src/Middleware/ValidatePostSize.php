<?php

namespace Moggie\Middleware;

use Moggie\Http\Exceptions\HttpException;

/**
 * Class ValidatePostSize
 *
 * @package \Moggie\Middleware
 */
class ValidatePostSize
{
    public function handle(Request $request, Closure $next): Response
    {
        $maxSize = $this->getPostMaxSize();

        if ($maxSize > 0 && $request->getContentLength() > $maxSize) {
            throw new HttpException(413, 'Payload too large');
        }

        return $next($request);
    }

    protected function getPostMaxSize(): int
    {
        $postMaxSize = ini_get('post_max_size');

        if (!$postMaxSize) {
            return 0;
        }

        return $this->parseSize($postMaxSize);
    }

    protected function parseSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int)$size;

        switch ($last) {
            case 'm':
            case 'g':
                $size *= 1024;
                break;
            case 'k':
                $size *= 1024;
        }

        return $size;
    }

    protected function getContentLength(Request $request): int
    {
        return (int)$request->header('Content-Length', 0);
    }
}
