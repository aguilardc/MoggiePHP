<?php

declare(strict_types=1);

namespace Moggie\Http;

use InvalidArgumentException;

class JsonResponse extends Response
{
    protected array $data;
    protected int $encodingOptions;

    public function __construct(
        array $data = [],
        int   $status = 200,
        array $headers = [],
        int   $encodingOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    )
    {
        $this->data = $data;
        $this->encodingOptions = $encodingOptions;

        parent::__construct('', $status, $headers);

        $this->setHeader('Content-Type', 'application/json');
        $this->setData($data);
    }

    public static function make(array|string $data = [], int $status = 200, array $headers = [], int $options = 0): self
    {
        return new static($data, $status, $headers, $options);
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        try {
            $this->content = $this->encode($data);
        } catch (\JsonException $e) {
            throw new InvalidArgumentException('Failed to encode data as JSON: ' . $e->getMessage(), 0, $e);
        }

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setEncodingOptions(int $options): self
    {
        $this->encodingOptions = $options;
        return $this->setData($this->data);
    }

    public function getEncodingOptions(): int
    {
        return $this->encodingOptions;
    }

    /**
     * @throws \JsonException
     */
    protected function encode(array $data): string
    {
        $json = json_encode($data, $this->encodingOptions);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \JsonException(json_last_error_msg(), json_last_error());
        }

        return $json;
    }

    public function withData(array $data): self
    {
        $clone = clone $this;
        $clone->setData($data);
        return $clone;
    }

    public function merge(array $data): self
    {
        return $this->setData(array_merge($this->data, $data));
    }

    public function set(string $key, $value): self
    {
        $data = $this->data;
        $data[$key] = $value;
        return $this->setData($data);
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function remove(string $key): self
    {
        $data = $this->data;
        unset($data[$key]);
        return $this->setData($data);
    }

    public function only(array $keys): self
    {
        return $this->setData(array_intersect_key($this->data, array_flip($keys)));
    }

    public function except(array $keys): self
    {
        return $this->setData(array_diff_key($this->data, array_flip($keys)));
    }

    public function pagination(array $items, int $total, int $page, int $perPage, string $path = ''): self
    {
        $lastPage = (int)ceil($total / $perPage);

        $pagination = [
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'from' => ($page - 1) * $perPage + 1,
                'last_page' => $lastPage,
                'per_page' => $perPage,
                'to' => min($page * $perPage, $total),
                'total' => $total,
            ],
            'links' => [
                'first' => $path ? $path . '?page=1' : null,
                'last' => $path ? $path . '?page=' . $lastPage : null,
                'prev' => $page > 1 && $path ? $path . '?page=' . ($page - 1) : null,
                'next' => $page < $lastPage && $path ? $path . '?page=' . ($page + 1) : null,
            ]
        ];

        return $this->setData($pagination);
    }

    public function success(string $message = 'Success', array $data = []): self
    {
        return $this->setData([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function error(string $message = 'Error', array $errors = [], int $code = null): self
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if ($code !== null) {
            $response['code'] = $code;
        }

        return $this->setData($response);
    }

    public function resource(array $data, array $meta = []): self
    {
        $response = ['data' => $data];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return $this->setData($response);
    }

    public function collection(array $items, array $meta = []): self
    {
        return $this->resource($items, $meta);
    }

    public function created(array $data = [], string|array $message = 'Resource created successfully'): self
    {
        $this->setStatusCode(static::HTTP_CREATED);

        return $this->setData([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function updated(array $data = [], string $message = 'Resource updated successfully'): self
    {
        return $this->setData([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function deleted(string $message = 'Resource deleted successfully'): self
    {
        return $this->setData([
            'success' => true,
            'message' => $message
        ]);
    }

    public function noContent(array $headers = []): self
    {
        $this->setStatusCode(static::HTTP_NO_CONTENT);
        $this->setData([]);
        return $this;
    }

    public function notFound(string $message = 'Resource not found'): self
    {
        $this->setStatusCode(static::HTTP_NOT_FOUND);

        return $this->error($message, [], static::HTTP_NOT_FOUND);
    }

    public function unauthorized(string $message = 'Unauthorized', array $headers = []): self
    {
        $this->setStatusCode(static::HTTP_UNAUTHORIZED);

        return $this->error($message, [], static::HTTP_UNAUTHORIZED);
    }

    public function forbidden(string $message = 'Forbidden', array $headers = []): self
    {
        $this->setStatusCode(static::HTTP_FORBIDDEN);

        return $this->error($message, [], static::HTTP_FORBIDDEN);
    }

    public function validationError(array $errors, string $message = 'Validation failed'): self
    {
        $this->setStatusCode(static::HTTP_UNPROCESSABLE_ENTITY);

        return $this->error($message, $errors, static::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function serverError(string $message = 'Internal server error'): self
    {
        $this->setStatusCode(static::HTTP_INTERNAL_SERVER_ERROR);

        return $this->error($message, [], static::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function transform(callable $transformer): self
    {
        $transformedData = $transformer($this->data);
        return $this->setData($transformedData);
    }

    public function wrap(string $key): self
    {
        return $this->setData([$key => $this->data]);
    }

    public function unwrap(string $key): self
    {
        if (isset($this->data[$key])) {
            return $this->setData($this->data[$key]);
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * @throws \JsonException
     */
    public function toJson(int $options = 0): string
    {
        return $this->encode($this->data);
    }

    public function __toString(): string
    {
        return $this->getContent();
    }

    public function __clone()
    {
        // Ensure data is properly cloned
        $this->data = unserialize(serialize($this->data));
    }
}
