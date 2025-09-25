<?php

namespace Moggie\Validation\Exceptions;

use Moggie\Http\Exceptions\UnprocessableEntityHttpException;

/**
 * Class ValidationException
 *
 * @package \Moggie\Validation\Exceptions
 */
class ValidationException extends UnprocessableEntityHttpException
{
    protected array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;

        parent::__construct($errors, 'The given data was invalid.');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }

        return null;
    }
}
