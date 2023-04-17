<?php

namespace core;

use App\interface\crud;

class Request
{
    private string $controller;
    private string $method;
    private array $parameters = [];

    public function __construct(array $data)
    {
        $this->setController($data['controller']);
        $this->setMethod($data['method']);
        $this->setParameters($data['parameters']);
    }

    /**
     * @return crud|null
     */
    public function getController(): ?crud
    {
        $class = "\\App\\controllers\\{$this->controller}Controller";
        return class_exists($class) ? new $class() : null;
    }

    /**
     * @param string $controller
     */
    private function setController(string $controller): void
    {
        $this->controller = $controller;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    private function setMethod(string $method): void
    {
        $this->method = $method;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     */
    private function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function toArray(): array
    {
        return $this->all();
    }

}