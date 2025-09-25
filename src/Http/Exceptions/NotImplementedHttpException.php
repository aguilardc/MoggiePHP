<?php

namespace Moggie\Http\Exceptions;

/**
 * Class NotImplementedHttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class NotImplementedHttpException extends HttpException
{
    public function __construct(
        string     $message = 'Not Implemented',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(501, $message, $headers, $code, $previous);
    }
}
