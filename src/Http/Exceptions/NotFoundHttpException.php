<?php

namespace Moggie\Http\Exceptions;

/**
 * Class NotFoundHttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class NotFoundHttpException extends HttpException
{
    public function __construct(
        string     $message = 'Not Found',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(404, $message, $headers, $code, $previous);
    }
}
