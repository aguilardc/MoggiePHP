<?php

namespace Moggie\Container;

/**
 * Class RewindableGenerator
 *
 * @package \Moggie\Container
 */
class RewindableGenerator
{
    protected $generator;
    protected int $count;

    public function __construct(callable $generator, int $count)
    {
        $this->count = $count;
        $this->generator = $generator;
    }

    public function getIterator(): Generator
    {
        return ($this->generator)();
    }

    public function count(): int
    {
        return $this->count;
    }
}
