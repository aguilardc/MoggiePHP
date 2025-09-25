<?php

namespace Moggie\Http\Exceptions;

/**
 * Class MethodNotAllowedHttpException
 *
 * @package \Moggie\Http\Exceptions
 */
class MethodNotAllowedHttpException extends HttpException
{
    protected array $allowedMethods;

    public function __construct(
        array      $allowedMethods = [],
        string     $message = 'Method Not Allowed',
        array      $headers = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        $this->allowedMethods = $allowedMethods;

        if (!empty($allowedMethods)) {
            $headers['Allow'] = implode(', ', $allowedMethods);
        }

        parent::__construct(405, $message, $headers, $code, $previous);
    }

    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
