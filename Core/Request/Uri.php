<?php

# ============================================================================ #

/**
 *  M O G G I E: a PHP micro-framework.
 *
 * @copyright   Copyright (c) 2023, Nevison Aguilar <aguilardc1105@gmail.com>
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 *
 */

namespace Core\Request;

use Psr\Http\Message\UriInterface;

final class Uri implements UriInterface
{
    private const SCHEMES = ['http' => 80, 'https' => 443];
    private const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';
    private const CHAR_SUB_DELIMITS = '!\$&\'\(\)\*\+,;=';
    private const CHAR_GEN_DELIMITS = ':\/\?#\[\]@';

    /** @var string Uri scheme. */
    private string $scheme = '';

    /** @var string Uri user info. */
    private string $userInfo = '';

    /** @var string Uri host. */
    private string $host = '';

    /** @var int|null Uri port. */
    private ?int $port;

    /** @var string Uri path. */
    private string $path = '';

    /** @var string Uri query string. */
    private string $query = '';

    /** @var string Uri fragment. */
    private string $fragment = '';

    public function __construct(string $uri)
    {
        if ($uri !== '') {

            if (false === $parts = parse_url($uri)) {
                throw new \InvalidArgumentException("Unable to parse URI: {$uri}");
            }

            $this->scheme = isset($parts['scheme']) ? strtr($parts['scheme'], UPPERCASE, LOWERCASE) : '';
            $this->userInfo = $parts['user'] ?? '';
            $this->host = isset($parts['host']) ? strtr($parts['host'], UPPERCASE, LOWERCASE) : '';
            $this->port = isset($parts['port']) ? $this->filterPort($parts['port']) : '';
            $this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
            $this->query = isset($parts['query']) ? $this->filterQueryAndFragment($parts['query']) : '';
            $this->fragment = isset($parts['fragment']) ? $this->filterQueryAndFragment($parts['fragment']) : '';
            if (isset($parts['pass'])) {
                $this->userInfo .= ':' . $parts['pass'];
            }
        }
    }

    public function __toString(): string
    {
        return self::createUriString($this->scheme, $this->getAuthority(), $this->path, $this->query, $this->fragment);
    }

    /**
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getAuthority(): string
    {
        if ('' === $this->host) {
            return '';
        }

        $authority = $this->host;
        if ('' !== $this->userInfo) {
            $authority = $this->userInfo . '@' . $authority;
        }

        if (null !== $this->port) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * @return string
     */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        $path = $this->path;

        if ('' !== $path && '/' !== $path[0]) {
            if ('' !== $this->host) {
                // If the path is rootless and an authority is present, the path MUST be prefixed by "/"
                $path = '/' . $path;
            }
        } elseif (isset($path[1]) && '/' === $path[1]) {
            // If the path is starting with more than one "/", the
            // starting slashes MUST be reduced to one.
            $path = '/' . \ltrim($path, '/');
        }

        return $path;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * @param $scheme
     * @return Uri
     */
    public function withScheme($scheme): UriInterface
    {
        if (!is_string($scheme)) {
            throw new \InvalidArgumentException('Scheme must be a string');
        }

        if ($this->scheme === $scheme = strtr($scheme, UPPERCASE, LOWERCASE)) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        $new->port = $new->filterPort($new->port);

        return $new;
    }

    /**
     * @param $user
     * @param null $password
     * @return Uri
     */
    public function withUserInfo($user, $password = null): UriInterface
    {
        if (!is_string($user)) {
            throw new \InvalidArgumentException('User must be a string');
        }

        $info = preg_replace_callback('/[' . self::CHAR_GEN_DELIMITS . self::CHAR_SUB_DELIMITS . ']++/', [__CLASS__, 'rawUrlEncodeMatchZero'], $user);
        if (null !== $password && '' !== $password) {
            if (!is_string($password)) {
                throw new \InvalidArgumentException('Password must be a string');
            }

            $info .= ':' . \preg_replace_callback('/[' . self::CHAR_GEN_DELIMITS . self::CHAR_SUB_DELIMITS . ']++/', [__CLASS__, 'rawUrlEncodeMatchZero'], $password);
        }

        if ($this->userInfo === $info) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;

        return $new;
    }

    /**
     * @return static
     */
    public function withHost($host): UriInterface
    {
        if (!is_string($host)) {
            throw new \InvalidArgumentException('Host must be a string');
        }

        if ($this->host === $host = strtr($host, UPPERCASE, LOWERCASE)) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    /**
     * @return static
     */
    public function withPort($port): UriInterface
    {
        if ($this->port === $port = $this->filterPort($port)) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    /**
     * @return static
     */
    public function withPath($path): UriInterface
    {
        if ($this->path === $path = $this->filterPath($path)) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /**
     * @return static
     */
    public function withQuery($query): UriInterface
    {
        if ($this->query === $query = $this->filterQueryAndFragment($query)) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /**
     * @return static
     */
    public function withFragment($fragment): UriInterface
    {
        if ($this->fragment === $fragment = $this->filterQueryAndFragment($fragment)) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    /**
     * Create a URI string from its various parts.
     */
    private static function createUriString(string $scheme, string $authority, string $path, string $query, string $fragment): string
    {
        $uri = '';
        if ('' !== $scheme) {
            $uri .= $scheme . ':';
        }

        if ('' !== $authority) {
            $uri .= '//' . $authority;
        }

        if ('' !== $path) {
            if ('/' !== $path[0]) {
                if ('' !== $authority) {
                    // If the path is rootless and an authority is present, the path MUST be prefixed by "/"
                    $path = '/' . $path;
                }
            } elseif (isset($path[1]) && '/' === $path[1]) {
                if ('' === $authority) {
                    // If the path is starting with more than one "/" and no authority is present, the
                    // starting slashes MUST be reduced to one.
                    $path = '/' . \ltrim($path, '/');
                }
            }

            $uri .= $path;
        }

        if ('' !== $query) {
            $uri .= '?' . $query;
        }

        if ('' !== $fragment) {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }

    /**
     * Is a given port non-standard for the current scheme?
     */
    private static function isNonStandardPort(string $scheme, int $port): bool
    {
        return !isset(self::SCHEMES[$scheme]) || $port !== self::SCHEMES[$scheme];
    }

    /**
     * @param $port
     * @return int|null
     */
    private function filterPort($port): ?int
    {
        if (is_null($port)) {
            return null;
        }

        $port = (int)$port;

        if (!in_array($port, range(0, 65535))) {
            throw new \InvalidArgumentException("Invalid port {$port}. Must be between 0 and 65535", $port);
        }

        return self::isNonStandardPort($this->scheme, $port) ? $port : null;
    }

    /**
     * @param $path
     * @return string
     */
    private function filterPath($path): string
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('path must be a string');
        }

        return preg_replace_callback('/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMITS . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/', [__CLASS__, 'rawUrlEncodeMatchZero'], $path);
    }

    /**
     * @param $str
     * @return string
     */
    private function filterQueryAndFragment($str): string
    {
        if (!is_string($str)) {
            throw new \InvalidArgumentException('Query and fragment must be a string');
        }

        return preg_replace_callback('/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMITS . '%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/', [__CLASS__, 'rawUrlEncodeMatchZero'], $str);
    }

    /**
     * @param array $match
     * @return string
     */
    private static function rawUrlEncodeMatchZero(array $match): string
    {
        return rawurlencode($match[0]);
    }
}
