<?php

namespace Moggie\Middleware;

use Moggie\Http\Request;
use Moggie\Http\Response;

/**
 * Class SkippableMiddleware
 *
 * @package \Moggie\Middleware
 */
class SkippableMiddleware
{
    protected MiddlewareStack $stack;
    protected array $skippedMiddleware;

    public function __construct(MiddlewareStack $stack, array $skippedMiddleware)
    {
        $this->stack = $stack;
        $this->skippedMiddleware = $skippedMiddleware;
    }

    public function handle(Request $request, \Closure $destination, array $middleware = []): Response
    {
        $middleware = array_diff($middleware, $this->skippedMiddleware);
        $globalMiddleware = array_diff($this->stack->getGlobalMiddleware(), $this->skippedMiddleware);

        // Temporarily replace global middleware
        $originalGlobal = $this->stack->getGlobalMiddleware();

        $reflection = new \ReflectionClass($this->stack);
        $property = $reflection->getProperty('globalMiddleware');
        $property->setAccessible(true);
        $property->setValue($this->stack, $globalMiddleware);

        try {
            return $this->stack->handle($request, $destination, $middleware);
        } finally {
            $property->setValue($this->stack, $originalGlobal);
        }
    }
}
