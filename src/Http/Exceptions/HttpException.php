<?php

declare(strict_types=1);

namespace Moggie\Http\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Class HttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class HttpException extends RuntimeException
{
    protected int $statusCode;
    protected array $headers;

    public function __construct(
        int        $statusCode,
        string     $message = '',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }
}
