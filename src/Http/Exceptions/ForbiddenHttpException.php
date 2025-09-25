<?php

namespace Moggie\Http\Exceptions;

/**
 * Class ForbiddenHttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class ForbiddenHttpException extends HttpException
{
    public function __construct(
        string     $message = 'Forbidden',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(403, $message, $headers, $code, $previous);
    }
}
