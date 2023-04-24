<?php

namespace Core\Request;

class Parameters
{
    /** @var array|null */
    private ?array $all = null;

    /**
     * @param array|null $parameters
     */
    public function __construct(?array $parameters)
    {
        if (!is_array($parameters)) {
            return null;
        }

        $this->setAll($parameters);
    }

    /**
     * @return array|null
     */
    public function getAll(): ?array
    {
        return $this->all;
    }

    /**
     * @param array|null $all
     */
    public function setAll(?array $all): void
    {
        foreach ($all as $key => $value) {
            $parameter = new Parameter($value);
            $this->all[$key] = $parameter;
        }
    }


}
