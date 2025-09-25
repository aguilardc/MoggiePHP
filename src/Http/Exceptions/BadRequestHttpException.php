<?php

namespace Moggie\Http\Exceptions;

/**
 * Class BadRequestHttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class BadRequestHttpException extends HttpException
{
    public function __construct(
        string     $message = 'Bad Request',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct(400, $message, $headers, $code, $previous);
    }
}
