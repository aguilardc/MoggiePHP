<?php

namespace Moggie\Http\Exceptions;

/**
 * Class UnauthorizedHttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class UnauthorizedHttpException extends HttpException
{
    public function __construct(
        string     $message = 'Unauthorized',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(401, $message, $headers, $code, $previous);
    }
}
