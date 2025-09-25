<?php

namespace Moggie\Http\Exceptions;

/**
 * Class GatewayTimeoutHttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class GatewayTimeoutHttpException extends HttpException
{
    public function __construct(
        string     $message = 'Gateway Timeout',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(504, $message, $headers, $code, $previous);
    }
}
