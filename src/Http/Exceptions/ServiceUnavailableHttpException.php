<?php

namespace Moggie\Http\Exceptions;

/**
 * Class ServiceUnavailableHttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class ServiceUnavailableHttpException extends HttpException
{
    protected ?int $retryAfter;

    public function __construct(
        ?int       $retryAfter = null,
        string     $message = 'Service Unavailable',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        $this->retryAfter = $retryAfter;

        if ($retryAfter !== null) {
            $headers['Retry-After'] = (string)$retryAfter;
        }

        parent::__construct(503, $message, $headers, $code, $previous);
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
