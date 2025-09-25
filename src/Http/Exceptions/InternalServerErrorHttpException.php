<?php

namespace Moggie\Http\Exceptions;

/**
 * Class InternalServerErrorHttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class InternalServerErrorHttpException extends HttpException
{
    public function __construct(
        string     $message = 'Internal Server Error',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(500, $message, $headers, $code, $previous);
    }
}
