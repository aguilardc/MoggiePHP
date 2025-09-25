<?php

namespace Moggie\Http\Exceptions;

/**
 * Class BadGatewayHttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class BadGatewayHttpException extends HttpException
{
    public function __construct(
        string     $message = 'Bad Gateway',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(502, $message, $headers, $code, $previous);
    }
}
