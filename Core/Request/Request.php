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

use Psr\Http\Message\{RequestInterface, StreamInterface, UriInterface};

class Request implements RequestInterface
{
    use RequestTrait;
    use MessageTrait;

    private array $parameters;

    /**
     * @param string|UriInterface $uri URI
     * @param array|null $params
     * @param array $headers Request headers
     * @param string|StreamInterface|null $body Request body
     * @param string $version Protocol version
     */
    public function __construct(UriInterface|string $uri, array $params = null, array $headers = [], StreamInterface|string $body = null, string $version = '1.1')
    {
        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $version;
        $this->setParameters(new Parameters($params));

        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }

        if ($body !== '' && $body !== null) {
            $this->stream = Stream::create($body);
        }
    }

    /**
     * @param Parameters $parameters
     * @return void
     */
    public function setParameters(Parameters $parameters): void
    {
        if (is_null($parameters->getAll())) {
            return;
        }
        $this->parameters = $parameters->getAll();
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param string $name
     * @return string
     */
    public function getParameter(string $name): string
    {
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name]->get();
        }
        throw new \InvalidArgumentException('Parameter don\'t exist');
    }

    /**
     * @param $name
     * @return string
     */
    public function get($name): string
    {
        return $this->getParameter($name);
    }
}
