<?php
declare(strict_types=1);

namespace Moggie\Http;

class Request
{
    protected array $query;
    protected array $request;
    protected array $attributes;
    protected array $cookies;
    protected array $files;
    protected array $server;
    protected array $headers;
    protected ?string $content = null;
    protected array $routeParameters = [];
    protected $user = null;
    protected ?array $json = null;
    protected ?array $all = null;

    public function __construct(
        array   $query = [],
        array   $request = [],
        array   $attributes = [],
        array   $cookies = [],
        array   $files = [],
        array   $server = [],
        ?string $content = null,
    )
    {
        $this->query = $query;
        $this->request = $request;
        $this->attributes = $attributes;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->server = $server;
        $this->content = $content;
        $this->headers = $this->initializeHeaders();
    }

    public static function capture(): self
    {
        return self::createFromGlobals();
    }

    public static function createFromGlobals(): self
    {
        return new static(
            $_GET,
            $_POST,
            [],
            $_COOKIE,
            $_FILES,
            $_SERVER,
            file_get_contents('php://input') ?: null
        );
    }

    public static function create(
        string  $uri,
        string  $method = 'GET',
        array   $parameters = [],
        array   $cookies = [],
        array   $files = [],
        array   $server = [],
        ?string $content = null
    ): self
    {
        $server = array_replace([
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'MoggiePHP',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
        ], $server);

        $server['PATH_INFO'] = '';
        $server['REQUEST_METHOD'] = strtoupper($method);

        $components = parse_url($uri);
        if (isset($components['host'])) {
            $server['SERVER_NAME'] = $components['host'];
            $server['HTTP_HOST'] = $components['host'];
        }

        if (isset($components['scheme'])) {
            if ($components['scheme'] === 'https') {
                $server['HTTPS'] = 'on';
                $server['SERVER_PORT'] = 443;
            } else {
                unset($server['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }

        if (isset($components['port'])) {
            $server['SERVER_PORT'] = $components['port'];
            $server['HTTP_HOST'] .= ':' . $components['port'];
        }

        if (isset($components['user'])) {
            $server['PHP_AUTH_USER'] = $components['user'];
        }

        if (isset($components['pass'])) {
            $server['PHP_AUTH_PW'] = $components['pass'];
        }

        if (!isset($components['path'])) {
            $components['path'] = '/';
        }

        switch (strtoupper($method)) {
            case 'POST':
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                if (!isset($server['CONTENT_TYPE'])) {
                    $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
                }
                break;
            // fall through
            case 'GET':
            default:
                $server['REQUEST_URI'] = $components['path'] . (isset($components['query']) ? '?' . $components['query'] : '');
                $server['QUERY_STRING'] = $components['query'] ?? '';
                break;
        }

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $request = $parameters;
            $query = [];
        } else {
            $request = [];
            $query = $parameters;
        }

        if (isset($components['query'])) {
            parse_str(html_entity_decode($components['query']), $qs);
            $query = array_replace($qs, $query);
        }

        return new static($query, $request, [], $cookies, $files, $server, $content);
    }

    protected function initializeHeaders(): array
    {
        $headers = [];

        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = substr($key, 5);
                $name = str_replace('_', '-', $name);
                $name = ucwords(strtolower($name), '-');
                $headers[$name] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $name = str_replace('_', '-', $key);
                $name = ucwords(strtolower($name), '-');
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    public function getMethod(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';

        // Check for method override
        if ($method === 'POST') {
            if ($override = $this->input('_method')) {
                return strtoupper($override);
            }

            if ($override = $this->header('X-HTTP-Method-Override')) {
                return strtoupper($override);
            }
        }

        return strtoupper($method);
    }

    public function isMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    public function isOptions(): bool
    {
        return $this->isMethod('OPTIONS');
    }

    public function isHead(): bool
    {
        return $this->isMethod('HEAD');
    }

    public function getUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    public function getPathInfo(): string
    {
        $pathInfo = $this->server['PATH_INFO'] ?? '';

        if (empty($pathInfo)) {
            $requestUri = $this->getUri();
            $scriptName = $this->server['SCRIPT_NAME'] ?? '';

            if (str_starts_with($requestUri, $scriptName)) {
                $pathInfo = substr($requestUri, strlen($scriptName));
            } else {
                $pathInfo = $requestUri;
            }
        }

        // Remove query string
        if (($pos = strpos($pathInfo, '?')) !== false) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }

        return $pathInfo ?: '/';
    }

    public function getQueryString(): ?string
    {
        return $this->server['QUERY_STRING'] ?? null;
    }

    public function fullUrl(): string
    {
        $scheme = $this->getScheme();
        $host = $this->getHost();
        $port = $this->getPort();
        $uri = $this->getUri();

        $url = $scheme . '://' . $host;

        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $url .= ':' . $port;
        }

        return $url . $uri;
    }

    public function url(): string
    {
        return rtrim($this->fullUrl(), '?' . $this->getQueryString());
    }

    public function root(): string
    {
        return rtrim($this->getSchemeAndHttpHost() . $this->getBasePath(), '/');
    }

    public function getSchemeAndHttpHost(): string
    {
        return $this->getScheme() . '://' . $this->getHttpHost();
    }

    public function getBasePath(): string
    {
        $filename = basename($this->server['SCRIPT_FILENAME'] ?? '');
        $baseUrl = $this->getBaseUrl();

        if (empty($baseUrl)) {
            return '';
        }

        if (basename($baseUrl) === $filename) {
            $basePath = dirname($baseUrl);
        } else {
            $basePath = $baseUrl;
        }

        if ('\\' === DIRECTORY_SEPARATOR) {
            $basePath = str_replace('\\', '/', $basePath);
        }

        return rtrim($basePath, '/');
    }

    public function getBaseUrl(): string
    {
        $requestUri = $this->getRequestUri();

        if (null === $requestUri) {
            return '';
        }

        $filename = basename($this->server['SCRIPT_FILENAME'] ?? '');

        if (basename($this->server['SCRIPT_NAME'] ?? '') === $filename) {
            $baseUrl = $this->server['SCRIPT_NAME'];
        } elseif (basename($this->server['PHP_SELF'] ?? '') === $filename) {
            $baseUrl = $this->server['PHP_SELF'];
        } elseif (basename($this->server['ORIG_SCRIPT_NAME'] ?? '') === $filename) {
            $baseUrl = $this->server['ORIG_SCRIPT_NAME'];
        } else {
            $path = $this->server['PHP_SELF'] ?? '';
            $segs = explode('/', trim($path, '/'));
            $segs = array_reverse($segs);
            $index = 0;
            $last = count($segs);
            $baseUrl = '';

            do {
                $seg = $segs[$index];
                $baseUrl = '/' . $seg . $baseUrl;
                ++$index;
            } while ($last > $index && (false !== $pos = strpos($requestUri, $baseUrl)) && 0 != $pos);
        }

        $requestUri = $this->getRequestUri();

        if (str_starts_with($requestUri, $baseUrl)) {
            return $baseUrl;
        }

        if (str_starts_with($requestUri, dirname($baseUrl))) {
            return rtrim(dirname($baseUrl), '/');
        }

        $truncatedRequestUri = $requestUri;
        if (false !== $pos = strpos($requestUri, '?')) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl);
        if (empty($basename) || !strpos(rawurldecode($truncatedRequestUri), $basename)) {
            return '';
        }

        if (strlen($requestUri) >= strlen($baseUrl) && (false !== $pos = strpos($requestUri, $baseUrl)) && 0 !== $pos) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return rtrim($baseUrl, '/');
    }

    public function getRequestUri(): ?string
    {
        if (isset($this->server['REQUEST_URI'])) {
            return $this->server['REQUEST_URI'];
        }

        if (isset($this->server['HTTP_X_ORIGINAL_URL'])) {
            return $this->server['HTTP_X_ORIGINAL_URL'];
        }

        if (isset($this->server['HTTP_X_REWRITE_URL'])) {
            return $this->server['HTTP_X_REWRITE_URL'];
        }

        if (isset($this->server['IIS_WasUrlRewritten']) && $this->server['IIS_WasUrlRewritten'] == '1' && isset($this->server['UNENCODED_URL'])) {
            return $this->server['UNENCODED_URL'];
        }

        if (isset($this->server['ORIG_PATH_INFO'])) {
            $requestUri = $this->server['ORIG_PATH_INFO'];
            if ('' != $this->server['QUERY_STRING']) {
                $requestUri .= '?' . $this->server['QUERY_STRING'];
            }
            return $requestUri;
        }

        return null;
    }

    public function input(?string $key = null, $default = null)
    {
        if ($this->all === null) {
            $this->all = $this->getInputSource();
        }

        if ($key === null) {
            return $this->all;
        }

        return $this->get($this->all, $key, $default);
    }

    protected function getInputSource(): array
    {
        if ($this->isJson()) {
            return $this->json();
        }

        return array_merge($this->query, $this->request);
    }

    protected function get(array $array, string $key, $default = null)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (!str_contains($key, '.')) {
            return $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    public function query(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }

        return $this->get($this->query, $key, $default);
    }

    public function post(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->request;
        }

        return $this->get($this->request, $key, $default);
    }

    public function has(...$keys): bool
    {
        $input = $this->input();

        foreach ($keys as $key) {
            if (!array_key_exists($key, $input) || $input[$key] === null) {
                return false;
            }
        }

        return true;
    }

    public function hasAny(...$keys): bool
    {
        $input = $this->input();

        foreach ($keys as $key) {
            if (array_key_exists($key, $input) && $input[$key] !== null) {
                return true;
            }
        }

        return false;
    }

    public function filled(string $key): bool
    {
        return !empty($this->input($key));
    }

    public function missing(string $key): bool
    {
        return !$this->has($key);
    }

    public function only(...$keys): array
    {
        $input = $this->input();
        $keys = is_array($keys[0]) ? $keys[0] : $keys;

        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $input)) {
                $result[$key] = $input[$key];
            }
        }

        return $result;
    }

    public function except(...$keys): array
    {
        $input = $this->input();
        $keys = is_array($keys[0]) ? $keys[0] : $keys;

        return array_diff_key($input, array_flip($keys));
    }

    public function header(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }

        $key = str_replace('_', '-', strtolower($key));
        $normalizedKey = ucwords($key, '-');

        return $this->headers[$normalizedKey] ?? $default;
    }

    public function hasHeader(string $key): bool
    {
        return $this->header($key) !== null;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');

        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    public function ip(): string
    {
        $clientIp = $this->server['HTTP_CLIENT_IP'] ?? null;
        $forwardedIp = $this->server['HTTP_X_FORWARDED_FOR'] ?? null;
        $remoteIp = $this->server['REMOTE_ADDR'] ?? '127.0.0.1';

        if ($clientIp) {
            return $clientIp;
        }

        if ($forwardedIp) {
            return trim(explode(',', $forwardedIp)[0]);
        }

        return $remoteIp;
    }

    public function userAgent(): ?string
    {
        return $this->header('User-Agent');
    }

    public function isSecure(): bool
    {
        $https = $this->server['HTTPS'] ?? null;

        return !empty($https) && strtolower($https) !== 'off';
    }

    public function getScheme(): string
    {
        return $this->isSecure() ? 'https' : 'http';
    }

    public function getHost(): string
    {
        $host = $this->header('Host') ?? $this->server['SERVER_NAME'] ?? 'localhost';

        // Remove port from host if present
        if (str_contains($host, ':')) {
            $host = explode(':', $host)[0];
        }

        return $host;
    }

    public function getHttpHost(): string
    {
        return $this->header('Host') ?? $this->server['SERVER_NAME'] ?? 'localhost';
    }

    public function getPort(): int
    {
        return (int)($this->server['SERVER_PORT'] ?? ($this->isSecure() ? 443 : 80));
    }

    // Content type helpers
    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type', '');
        return str_contains($contentType, 'application/json');
    }

    public function expectsJson(): bool
    {
        return $this->isJson() || $this->wantsJson();
    }

    public function wantsJson(): bool
    {
        $acceptable = $this->getAcceptableContentTypes();
        return isset($acceptable[0]) && str_contains($acceptable[0], 'json');
    }

    public function getAcceptableContentTypes(): array
    {
        $accept = $this->header('Accept', '*/*');
        $types = [];

        foreach (explode(',', $accept) as $type) {
            $type = trim($type);
            if (str_contains($type, ';')) {
                $type = explode(';', $type)[0];
            }
            $types[] = trim($type);
        }

        return $types;
    }

    public function isXmlHttpRequest(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    // JSON handling
    public function json(): array
    {
        if ($this->json === null) {
            $this->json = $this->parseJson();
        }

        return $this->json;
    }

    protected function parseJson(): array
    {
        if ($this->content === null || $this->content === '') {
            return [];
        }

        $decoded = json_decode($this->content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $decoded ?? [];
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getContentLength(): int
    {
        return (int)$this->header('Content-Length', 0);
    }

    // Files
    public function file(?string $key = null)
    {
        if ($key === null) {
            return $this->files;
        }

        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) &&
            is_array($this->files[$key]) &&
            $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    public function allFiles(): array
    {
        return $this->files;
    }

    // Cookies
    public function cookie(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }

        return $this->cookies[$key] ?? $default;
    }

    public function hasCookie(string $key): bool
    {
        return array_key_exists($key, $this->cookies);
    }

    // Route parameters
    public function setRouteParameters(array $parameters): void
    {
        $this->routeParameters = $parameters;
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function route(?string $parameter = null, $default = null)
    {
        if ($parameter === null) {
            return $this->routeParameters;
        }

        return $this->routeParameters[$parameter] ?? $default;
    }

    // User
    public function setUser($user): void
    {
        $this->user = $user;
    }

    public function user()
    {
        return $this->user;
    }

    // Attributes
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    // Modify request data
    public function merge(array $input): void
    {
        $this->request = array_merge($this->request, $input);
        $this->all = null; // Reset cached input
    }

    public function replace(array $input): void
    {
        $this->request = $input;
        $this->all = null; // Reset cached input
    }

    // Convenience methods
    public function all(): array
    {
        return $this->input();
    }

    public function keys(): array
    {
        return array_keys($this->input());
    }

    public function toArray(): array
    {
        return $this->all();
    }

    // Magic methods
    public function __get(string $key)
    {
        return $this->input($key);
    }

    public function __set(string $key, $value): void
    {
        $this->request[$key] = $value;
        $this->all = null; // Reset cached input
    }

    public function __isset(string $key): bool
    {
        return $this->has($key);
    }

    public function __unset(string $key): void
    {
        unset($this->request[$key]);
        $this->all = null; // Reset cached input
    }
}
