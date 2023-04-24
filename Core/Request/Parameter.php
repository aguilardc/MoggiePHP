<?php

namespace Core\Request;

class Parameter
{
    private string $parameter;

    public function __construct(?string $parameter)
    {
        $this->set($parameter);
    }

    /**
     * @return string
     */
    public function get(): string
    {
        return $this->parameter;
    }

    /**
     * @param string $parameter
     */
    public function set(string $parameter): void
    {
        $this->parameter = $parameter;
    }


}
