<?php
declare(strict_types=1);

namespace Moggie\Validation;

/**
 * Class Validator
 *
 * @package \Moggie\Validation
 */
class Validator
{
    protected array $data;
    protected array $rules;
    protected array $messages;
    protected array $errors = [];
    protected array $customValidators = [];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new static($data, $rules, $messages);
    }

    public function validate(): array
    {
        $this->errors = [];

        foreach ($this->rules as $field => $rules) {
            $this->validateField($field, $rules);
        }

        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }

        return $this->getValidatedData();
    }

    public function fails(): bool
    {
        try {
            $this->validate();
            return false;
        } catch (ValidationException $e) {
            return true;
        }
    }

    public function passes(): bool
    {
        return !$this->fails();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getValidatedData(): array
    {
        $validated = [];

        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $this->data)) {
                $validated[$field] = $this->data[$field];
            }
        }

        return $validated;
    }

    protected function validateField(string $field, $rules): void
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $value = $this->getValue($field);
        $required = $this->hasRule($rules, 'required');

        // Si el campo no es requerido y está vacío, saltamos la validación
        if (!$required && $this->isEmpty($value)) {
            return;
        }

        foreach ($rules as $rule) {
            $this->validateRule($field, $value, $rule);
        }
    }

    protected function getValue(string $field)
    {
        if (str_contains($field, '.')) {
            return $this->getNestedValue($field);
        }

        return $this->data[$field] ?? null;
    }

    protected function getNestedValue(string $field)
    {
        $keys = explode('.', $field);
        $value = $this->data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    protected function hasRule(array $rules, string $ruleName): bool
    {
        return in_array($ruleName, $rules) ||
            in_array($ruleName, array_map(fn($rule) => explode(':', $rule)[0], $rules));
    }

    protected function isEmpty($value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    protected function validateRule(string $field, $value, string $rule): void
    {
        [$ruleName, $parameters] = $this->parseRule($rule);

        $method = 'validate' . ucfirst($ruleName);

        if (method_exists($this, $method)) {
            $passes = $this->$method($field, $value, $parameters);
        } elseif (isset($this->customValidators[$ruleName])) {
            $passes = call_user_func($this->customValidators[$ruleName], $field, $value, $parameters);
        } else {
            throw new \InvalidArgumentException("Validation rule '{$ruleName}' does not exist");
        }

        if (!$passes) {
            $this->addError($field, $this->getMessage($field, $ruleName, $parameters));
        }
    }

    protected function parseRule(string $rule): array
    {
        $segments = explode(':', $rule, 2);
        $ruleName = $segments[0];
        $parameters = isset($segments[1]) ? explode(',', $segments[1]) : [];

        return [$ruleName, $parameters];
    }

    protected function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    protected function getMessage(string $field, string $rule, array $parameters): string
    {
        $key = "{$field}.{$rule}";

        if (isset($this->messages[$key])) {
            return $this->formatMessage($this->messages[$key], $field, $parameters);
        }

        if (isset($this->messages[$rule])) {
            return $this->formatMessage($this->messages[$rule], $field, $parameters);
        }

        return $this->getDefaultMessage($field, $rule, $parameters);
    }

    protected function formatMessage(string $message, string $field, array $parameters): string
    {
        $message = str_replace(':attribute', $field, $message);

        foreach ($parameters as $index => $parameter) {
            $message = str_replace(":param{$index}", $parameter, $message);
            $message = str_replace(':' . ($index === 0 ? 'value' : "param{$index}"), $parameter, $message);
        }

        return $message;
    }

    protected function getDefaultMessage(string $field, string $rule, array $parameters): string
    {
        $messages = [
            'required' => "The {$field} field is required.",
            'string' => "The {$field} field must be a string.",
            'integer' => "The {$field} field must be an integer.",
            'numeric' => "The {$field} field must be a number.",
            'boolean' => "The {$field} field must be true or false.",
            'email' => "The {$field} field must be a valid email address.",
            'url' => "The {$field} field must be a valid URL.",
            'min' => "The {$field} field must be at least {$parameters[0]}.",
            'max' => "The {$field} field must not be greater than {$parameters[0]}.",
            'between' => "The {$field} field must be between {$parameters[0]} and {$parameters[1]}.",
            'size' => "The {$field} field must be {$parameters[0]}.",
            'in' => "The selected {$field} is invalid.",
            'not_in' => "The selected {$field} is invalid.",
            'alpha' => "The {$field} field must only contain letters.",
            'alpha_num' => "The {$field} field must only contain letters and numbers.",
            'alpha_dash' => "The {$field} field must only contain letters, numbers, dashes and underscores.",
            'regex' => "The {$field} field format is invalid.",
            'unique' => "The {$field} has already been taken.",
            'exists' => "The selected {$field} is invalid.",
            'confirmed' => "The {$field} confirmation does not match.",
            'same' => "The {$field} and {$parameters[0]} must match.",
            'different' => "The {$field} and {$parameters[0]} must be different.",
            'date' => "The {$field} field must be a valid date.",
            'date_format' => "The {$field} field must match the format {$parameters[0]}.",
            'before' => "The {$field} field must be a date before {$parameters[0]}.",
            'after' => "The {$field} field must be a date after {$parameters[0]}.",
            'json' => "The {$field} field must be a valid JSON string.",
            'array' => "The {$field} field must be an array.",
            'file' => "The {$field} field must be a file.",
            'image' => "The {$field} field must be an image.",
            'mimes' => "The {$field} field must be a file of type: " . implode(', ', $parameters) . ".",
            'nullable' => "The {$field} field may be null.",
        ];

        return $messages[$rule] ?? "The {$field} field is invalid.";
    }

    // Validation Rules
    protected function validateRequired(string $field, $value, array $parameters): bool
    {
        return !$this->isEmpty($value);
    }

    protected function validateString(string $field, $value, array $parameters): bool
    {
        return is_string($value);
    }

    protected function validateInteger(string $field, $value, array $parameters): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateNumeric(string $field, $value, array $parameters): bool
    {
        return is_numeric($value);
    }

    protected function validateBoolean(string $field, $value, array $parameters): bool
    {
        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
    }

    protected function validateEmail(string $field, $value, array $parameters): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateUrl(string $field, $value, array $parameters): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    protected function validateMin(string $field, $value, array $parameters): bool
    {
        if (!isset($parameters[0])) {
            return false;
        }

        $min = (float)$parameters[0];

        if (is_string($value)) {
            return strlen($value) >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return (float)$value >= $min;
    }

    protected function validateMax(string $field, $value, array $parameters): bool
    {
        if (!isset($parameters[0])) {
            return false;
        }

        $max = (float)$parameters[0];

        if (is_string($value)) {
            return strlen($value) <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return (float)$value <= $max;
    }

    protected function validateBetween(string $field, $value, array $parameters): bool
    {
        if (count($parameters) < 2) {
            return false;
        }

        $min = (float)$parameters[0];
        $max = (float)$parameters[1];

        if (is_string($value)) {
            $size = strlen($value);
        } elseif (is_array($value)) {
            $size = count($value);
        } else {
            $size = (float)$value;
        }

        return $size >= $min && $size <= $max;
    }

    protected function validateSize(string $field, $value, array $parameters): bool
    {
        if (!isset($parameters[0])) {
            return false;
        }

        $size = (float)$parameters[0];

        if (is_string($value)) {
            return strlen($value) === (int)$size;
        }

        if (is_array($value)) {
            return count($value) === (int)$size;
        }

        return (float)$value === $size;
    }

    protected function validateIn(string $field, $value, array $parameters): bool
    {
        return in_array($value, $parameters);
    }

    protected function validateNotIn(string $field, $value, array $parameters): bool
    {
        return !in_array($value, $parameters);
    }

    protected function validateAlpha(string $field, $value, array $parameters): bool
    {
        return is_string($value) && preg_match('/^[a-zA-Z]+$/', $value);
    }

    protected function validateAlphaNum(string $field, $value, array $parameters): bool
    {
        return is_string($value) && preg_match('/^[a-zA-Z0-9]+$/', $value);
    }

    protected function validateAlphaDash(string $field, $value, array $parameters): bool
    {
        return is_string($value) && preg_match('/^[a-zA-Z0-9_-]+$/', $value);
    }

    protected function validateRegex(string $field, $value, array $parameters): bool
    {
        if (!isset($parameters[0])) {
            return false;
        }

        return is_string($value) && preg_match($parameters[0], $value);
    }

    protected function validateConfirmed(string $field, $value, array $parameters): bool
    {
        $confirmationField = $field . '_confirmation';
        $confirmationValue = $this->getValue($confirmationField);

        return $value === $confirmationValue;
    }

    protected function validateSame(string $field, $value, array $parameters): bool
    {
        if (!isset($parameters[0])) {
            return false;
        }

        $otherValue = $this->getValue($parameters[0]);
        return $value === $otherValue;
    }

    protected function validateDifferent(string $field, $value, array $parameters): bool
    {
        if (!isset($parameters[0])) {
            return false;
        }

        $otherValue = $this->getValue($parameters[0]);
        return $value !== $otherValue;
    }

    protected function validateDate(string $field, $value, array $parameters): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return strtotime($value) !== false;
    }

    protected function validateDateFormat(string $field, $value, array $parameters): bool
    {
        if (!isset($parameters[0]) || !is_string($value)) {
            return false;
        }

        $date = \DateTime::createFromFormat($parameters[0], $value);
        return $date && $date->format($parameters[0]) === $value;
    }

    protected function validateJson(string $field, $value, array $parameters): bool
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    protected function validateArray(string $field, $value, array $parameters): bool
    {
        return is_array($value);
    }

    protected function validateNullable(string $field, $value, array $parameters): bool
    {
        return true; // Nullable is always valid
    }

    public function extend(string $rule, callable $callback): void
    {
        $this->customValidators[$rule] = $callback;
    }

    public static function extend(string $rule, callable $callback): void
    {
        static::$globalValidators[$rule] = $callback;
    }

    // Global custom validators
    protected static array $globalValidators = [];
}
