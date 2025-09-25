<?php

namespace Moggie\Http\Exceptions;

/**
 * Class ConflictHttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class ConflictHttpException extends HttpException
{
    public function __construct(
        string     $message = 'Conflict',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(409, $message, $headers, $code, $previous);
    }
}
