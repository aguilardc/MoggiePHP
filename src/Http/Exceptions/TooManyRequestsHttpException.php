<?php

namespace Moggie\Http\Exceptions;

/**
 * Class TooManyRequestsHttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class TooManyRequestsHttpException extends HttpException
{
    protected ?int $retryAfter;

    public function __construct(
        ?int       $retryAfter = null,
        string     $message = 'Too Many Requests',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        $this->retryAfter = $retryAfter;

        if ($retryAfter !== null) {
            $headers['Retry-After'] = (string)$retryAfter;
        }

        parent::__construct(429, $message, $headers, $code, $previous);
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
