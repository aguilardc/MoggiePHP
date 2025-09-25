<?php

namespace Moggie\Http\Exceptions;

/**
 * Class UnprocessableEntityHttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class UnprocessableEntityHttpException extends HttpException
{
    protected array $errors;

    public function __construct(
        array      $errors = [],
        string     $message = 'Unprocessable Entity',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        $this->errors = $errors;
        parent::__construct(422, $message, $headers, $code, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
