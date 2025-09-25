<?php

namespace Moggie\Container;

/**
 * Class ContextualBindingBuilder
 *
 * @package \Moggie\Container
 */
class ContextualBindingBuilder
{
    protected Container $container;
    protected string|array $concrete;
    protected ?string $needs = null;
    protected array $aliases = [];

    public function __construct(Container $container, string|array $concrete, array $aliases = [])
    {
        $this->concrete = $concrete;
        $this->container = $container;
        $this->aliases = $aliases;
    }

    public function needs(string $abstract): self
    {
        $this->needs = $abstract;

        return $this;
    }

    public function give($implementation): void
    {
        foreach (Arr::wrap($this->concrete) as $concrete) {
            $this->container->addContextualBinding($concrete, $this->needs, $implementation);
        }
    }

    public function giveTagged(string $tag): void
    {
        $this->give(function ($container) use ($tag) {
            $taggedServices = $container->tagged($tag);

            return is_array($taggedServices) ? $taggedServices : iterator_to_array($taggedServices);
        });
    }

    public function giveConfig(string $key, string $default = null): void
    {
        $this->give(function ($container) use ($key, $default) {
            return $container->get('config')->get($key, $default);
        });
    }
}
