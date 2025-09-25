<?php

declare(strict_types=1);

namespace Moggie\Http;

class Response
{
    protected int $statusCode;
    protected array $headers;
    protected string $content;
    protected string $version = '1.1';
    protected ?string $charset = null;

    // HTTP Status Codes
    public const HTTP_CONTINUE = 100;
    public const HTTP_SWITCHING_PROTOCOLS = 101;
    public const HTTP_PROCESSING = 102;
    public const HTTP_EARLY_HINTS = 103;
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_ACCEPTED = 202;
    public const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_RESET_CONTENT = 205;
    public const HTTP_PARTIAL_CONTENT = 206;
    public const HTTP_MULTI_STATUS = 207;
    public const HTTP_ALREADY_REPORTED = 208;
    public const HTTP_IM_USED = 226;
    public const HTTP_MULTIPLE_CHOICES = 300;
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_SEE_OTHER = 303;
    public const HTTP_NOT_MODIFIED = 304;
    public const HTTP_USE_PROXY = 305;
    public const HTTP_RESERVED = 306;
    public const HTTP_TEMPORARY_REDIRECT = 307;
    public const HTTP_PERMANENTLY_REDIRECT = 308;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_PAYMENT_REQUIRED = 402;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_NOT_ACCEPTABLE = 406;
    public const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const HTTP_REQUEST_TIMEOUT = 408;
    public const HTTP_CONFLICT = 409;
    public const HTTP_GONE = 410;
    public const HTTP_LENGTH_REQUIRED = 411;
    public const HTTP_PRECONDITION_FAILED = 412;
    public const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    public const HTTP_REQUEST_URI_TOO_LONG = 414;
    public const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    public const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const HTTP_EXPECTATION_FAILED = 417;
    public const HTTP_I_AM_A_TEAPOT = 418;
    public const HTTP_MISDIRECTED_REQUEST = 421;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_LOCKED = 423;
    public const HTTP_FAILED_DEPENDENCY = 424;
    public const HTTP_TOO_EARLY = 425;
    public const HTTP_UPGRADE_REQUIRED = 426;
    public const HTTP_PRECONDITION_REQUIRED = 428;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const HTTP_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_NOT_IMPLEMENTED = 501;
    public const HTTP_BAD_GATEWAY = 502;
    public const HTTP_SERVICE_UNAVAILABLE = 503;
    public const HTTP_GATEWAY_TIMEOUT = 504;
    public const HTTP_VERSION_NOT_SUPPORTED = 505;
    public const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;
    public const HTTP_INSUFFICIENT_STORAGE = 507;
    public const HTTP_LOOP_DETECTED = 508;
    public const HTTP_NOT_EXTENDED = 510;
    public const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;

    protected static array $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->setContent($content);
        $this->setStatusCode($status);
        $this->headers = [];

        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

        // Set default charset
        $this->charset = 'UTF-8';
    }

    // Static factory methods
    public static function make(string $content = '', int $status = 200, array $headers = []): self
    {
        return new static($content, $status, $headers);
    }

    public static function json(array $data = [], int $status = 200, array $headers = [], int $options = 0): JsonResponse
    {
        return new JsonResponse($data, $status, $headers, $options);
    }

    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        $response = new static($content, $status, $headers);
        $response->setHeader('Content-Type', 'text/html; charset=UTF-8');
        return $response;
    }

    public static function plainText(string $content, int $status = 200, array $headers = []): self
    {
        $response = new static($content, $status, $headers);
        $response->setHeader('Content-Type', 'text/plain; charset=UTF-8');
        return $response;
    }

    public static function xml(string $content, int $status = 200, array $headers = []): self
    {
        $response = new static($content, $status, $headers);
        $response->setHeader('Content-Type', 'application/xml; charset=UTF-8');
        return $response;
    }

    public static function csv(string $content, string $filename = 'export.csv', int $status = 200, array $headers = []): self
    {
        $headers = array_merge([
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], $headers);

        return new static($content, $status, $headers);
    }

    public static function download(string $content, string $filename, array $headers = []): self
    {
        $headers = array_merge([
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length' => strlen($content),
        ], $headers);

        return new static($content, 200, $headers);
    }

    // Quick response creators
    public static function ok(string $content = 'OK', array $headers = []): self
    {
        return new static($content, static::HTTP_OK, $headers);
    }

    public static function created(array $data = [], array $headers = []): JsonResponse
    {
        return static::json($data, static::HTTP_CREATED, $headers);
    }

    public static function accepted(array $data = [], array $headers = []): JsonResponse
    {
        return static::json($data, static::HTTP_ACCEPTED, $headers);
    }

    public static function noContent(array $headers = []): self
    {
        return new static('', static::HTTP_NO_CONTENT, $headers);
    }

    public static function badRequest(string $message = 'Bad Request', array $headers = []): JsonResponse
    {
        return static::json(['error' => $message], static::HTTP_BAD_REQUEST, $headers);
    }

    public static function unauthorized(string $message = 'Unauthorized', array $headers = []): JsonResponse
    {
        return static::json(['error' => $message], static::HTTP_UNAUTHORIZED, $headers);
    }

    public static function forbidden(string $message = 'Forbidden', array $headers = []): JsonResponse
    {
        return static::json(['error' => $message], static::HTTP_FORBIDDEN, $headers);
    }

    public static function notFound(string $message = 'Not Found', array $headers = []): JsonResponse
    {
        return static::json(['error' => $message], static::HTTP_NOT_FOUND, $headers);
    }

    public static function methodNotAllowed(array $allowedMethods = [], string $message = 'Method Not Allowed', array $headers = []): JsonResponse
    {
        if (!empty($allowedMethods)) {
            $headers['Allow'] = implode(', ', $allowedMethods);
        }

        return static::json(['error' => $message], static::HTTP_METHOD_NOT_ALLOWED, $headers);
    }

    public static function conflict(string $message = 'Conflict', array $headers = []): JsonResponse
    {
        return static::json(['error' => $message], static::HTTP_CONFLICT, $headers);
    }

    public static function unprocessableEntity(array $errors = [], string $message = 'Unprocessable Entity', array $headers = []): JsonResponse
    {
        $data = ['message' => $message];

        if (!empty($errors)) {
            $data['errors'] = $errors;
        }

        return static::json($data, static::HTTP_UNPROCESSABLE_ENTITY, $headers);
    }

    public static function tooManyRequests(int $retryAfter = null, string $message = 'Too Many Requests', array $headers = []): JsonResponse
    {
        if ($retryAfter !== null) {
            $headers['Retry-After'] = (string)$retryAfter;
        }

        return static::json(['error' => $message], static::HTTP_TOO_MANY_REQUESTS, $headers);
    }

    public static function internalServerError(string $message = 'Internal Server Error', array $headers = []): JsonResponse
    {
        return static::json(['error' => $message], static::HTTP_INTERNAL_SERVER_ERROR, $headers);
    }

    public static function serviceUnavailable(int $retryAfter = null, string $message = 'Service Unavailable', array $headers = []): JsonResponse
    {
        if ($retryAfter !== null) {
            $headers['Retry-After'] = (string)$retryAfter;
        }

        return static::json(['error' => $message], static::HTTP_SERVICE_UNAVAILABLE, $headers);
    }

    // Redirect responses
    public static function redirect(string $url, int $status = 302, array $headers = []): self
    {
        $response = new static('', $status, $headers);
        $response->setHeader('Location', $url);
        return $response;
    }

    public static function redirectTo(string $url, int $status = 302, array $headers = []): self
    {
        return static::redirect($url, $status, $headers);
    }

    public static function redirectAway(string $url, int $status = 302, array $headers = []): self
    {
        return static::redirect($url, $status, $headers);
    }

    public static function permanentRedirect(string $url, array $headers = []): self
    {
        return static::redirect($url, static::HTTP_MOVED_PERMANENTLY, $headers);
    }

    // Content methods
    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function appendContent(string $content): self
    {
        $this->content .= $content;
        return $this;
    }

    public function prependContent(string $content): self
    {
        $this->content = $content . $this->content;
        return $this;
    }

    // Status methods
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $code, ?string $text = null): self
    {
        $this->statusCode = $code;

        if ($text !== null) {
            static::$statusTexts[$code] = $text;
        }

        return $this;
    }

    public function getStatusText(): string
    {
        return static::$statusTexts[$this->statusCode] ?? 'Unknown';
    }

    public function withStatus(int $code, ?string $text = null): self
    {
        $clone = clone $this;
        return $clone->setStatusCode($code, $text);
    }

    // Header methods
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function getHeaderLine(string $name): string
    {
        return $this->getHeader($name) ?? '';
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[strtolower($name)] = $value;
        return $this;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->setHeader($name, $value);
        return $clone;
    }

    public function addHeader(string $name, string $value): self
    {
        $name = strtolower($name);

        if (isset($this->headers[$name])) {
            $this->headers[$name] .= ', ' . $value;
        } else {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    public function withAddedHeader(string $name, string $value): self
    {
        $clone = clone $this;
        return $clone->addHeader($name, $value);
    }

    public function removeHeader(string $name): self
    {
        unset($this->headers[strtolower($name)]);
        return $this;
    }

    public function withoutHeader(string $name): self
    {
        $clone = clone $this;
        $clone->removeHeader($name);
        return $clone;
    }

    // Cookie methods
    public function cookie(
        string  $name,
        string  $value,
        int     $expire = 0,
        string  $path = '/',
        string  $domain = '',
        bool    $secure = false,
        bool    $httpOnly = true,
        bool    $raw = false,
        ?string $sameSite = null
    ): self
    {
        $cookie = $raw ? $name . '=' . $value : rawurlencode($name) . '=' . rawurlencode($value);

        if ($expire !== 0) {
            $cookie .= '; expires=' . gmdate('D, d-M-Y H:i:s T', $expire);
            $cookie .= '; max-age=' . ($expire - time());
        }

        if (!empty($path)) {
            $cookie .= '; path=' . $path;
        }

        if (!empty($domain)) {
            $cookie .= '; domain=' . $domain;
        }

        if ($secure) {
            $cookie .= '; secure';
        }

        if ($httpOnly) {
            $cookie .= '; httponly';
        }

        if ($sameSite !== null) {
            $cookie .= '; samesite=' . $sameSite;
        }

        return $this->addHeader('Set-Cookie', $cookie);
    }

    public function withCookie(
        string  $name,
        string  $value,
        int     $expire = 0,
        string  $path = '/',
        string  $domain = '',
        bool    $secure = false,
        bool    $httpOnly = true,
        bool    $raw = false,
        ?string $sameSite = null
    ): self
    {
        $clone = clone $this;
        return $clone->cookie($name, $value, $expire, $path, $domain, $secure, $httpOnly, $raw, $sameSite);
    }

    public function expireCookie(string $name, string $path = '/', string $domain = ''): self
    {
        return $this->cookie($name, '', time() - 3600, $path, $domain);
    }

    // Content type methods
    public function contentType(string $type, string $charset = null): self
    {
        $value = $type;

        if ($charset !== null) {
            $value .= '; charset=' . $charset;
        } elseif ($this->charset !== null && !str_contains($type, 'charset=')) {
            $value .= '; charset=' . $this->charset;
        }

        return $this->setHeader('Content-Type', $value);
    }

    // Charset methods
    public function getCharset(): ?string
    {
        return $this->charset;
    }

    public function setCharset(string $charset): self
    {
        $this->charset = $charset;
        return $this;
    }

    // HTTP version methods
    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    public function withProtocolVersion(string $version): self
    {
        $clone = clone $this;
        $clone->version = $version;
        return $clone;
    }

    // Status check methods
    public function isInformational(): bool
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isRedirection(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    public function isOk(): bool
    {
        return $this->statusCode === 200;
    }

    public function isCreated(): bool
    {
        return $this->statusCode === 201;
    }

    public function isNoContent(): bool
    {
        return $this->statusCode === 204;
    }

    public function isForbidden(): bool
    {
        return $this->statusCode === 403;
    }

    public function isNotFound(): bool
    {
        return $this->statusCode === 404;
    }

    public function isEmpty(): bool
    {
        return in_array($this->statusCode, [204, 304]);
    }

    public function isRedirect(): bool
    {
        return in_array($this->statusCode, [301, 302, 303, 307, 308]);
    }

    // Cache methods
    public function setCache(array $options): self
    {
        if (isset($options['etag'])) {
            $this->setHeader('ETag', $options['etag']);
        }

        if (isset($options['last_modified'])) {
            if ($options['last_modified'] instanceof \DateTimeInterface) {
                $options['last_modified'] = $options['last_modified']->format('D, d M Y H:i:s') . ' GMT';
            }
            $this->setHeader('Last-Modified', $options['last_modified']);
        }

        if (isset($options['max_age'])) {
            $this->setHeader('Cache-Control', 'max-age=' . $options['max_age']);
        }

        if (isset($options['s_max_age'])) {
            $this->addHeader('Cache-Control', 's-maxage=' . $options['s_max_age']);
        }

        if (isset($options['private'])) {
            $this->addHeader('Cache-Control', 'private');
        }

        if (isset($options['public'])) {
            $this->addHeader('Cache-Control', 'public');
        }

        return $this;
    }

    public function setEtag(string $etag, bool $weak = false): self
    {
        if ($weak) {
            $etag = 'W/"' . $etag . '"';
        } else {
            $etag = '"' . $etag . '"';
        }

        return $this->setHeader('ETag', $etag);
    }

    public function setLastModified(\DateTimeInterface $date): self
    {
        return $this->setHeader('Last-Modified', $date->format('D, d M Y H:i:s') . ' GMT');
    }

    public function setMaxAge(int $value): self
    {
        return $this->setHeader('Cache-Control', 'max-age=' . $value);
    }

    public function setSharedMaxAge(int $value): self
    {
        return $this->addHeader('Cache-Control', 's-maxage=' . $value);
    }

    public function setPublic(): self
    {
        return $this->addHeader('Cache-Control', 'public');
    }

    public function setPrivate(): self
    {
        return $this->addHeader('Cache-Control', 'private');
    }

    // Send response
    public function send(): void
    {
        $this->sendHeaders();
        $this->sendContent();

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    public function sendHeaders(): self
    {
        if (headers_sent()) {
            return $this;
        }

        // Status line
        header(
            sprintf('HTTP/%s %d %s', $this->version, $this->statusCode, $this->getStatusText()),
            true,
            $this->statusCode
        );

        // Headers
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, false);
        }

        return $this;
    }

    public function sendContent(): self
    {
        echo $this->content;
        return $this;
    }

    // String representation
    public function __toString(): string
    {
        return sprintf(
                'HTTP/%s %d %s',
                $this->version,
                $this->statusCode,
                $this->getStatusText()
            ) . "\r\n" . $this->getHeadersAsString() . "\r\n" . $this->content;
    }

    protected function getHeadersAsString(): string
    {
        $headers = [];
        foreach ($this->headers as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        return implode("\r\n", $headers);
    }

    // Cloning
    public function __clone()
    {
        // Ensure deep cloning of arrays
        $this->headers = $this->headers;
    }
}
