<?php

namespace Yuloh\Container;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    /**
     * @var \Closure[]
     */
    private $definitions = [];

    public function get($id)
    {
        // If we have a binding for it, then it's a closure.
        // We can just invoke it and return the resolved instance.
        if ($this->has($id)) {
            return $this->definitions[$id]($this);
        }

        // Otherwise we are going to try and use reflection to "autowire"
        // the dependencies and instantiate this entry if it's a class.
        if (!class_exists($id)) {
            throw NotFoundException::create($id);
        }

        $reflector = new \ReflectionClass($id);

        if (!$reflector->isInstantiable()) {
            throw NotFoundException::create($id);
        }

        /** @var \ReflectionMethod|null */
        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $id();
        }

        $dependencies = array_map(
            function (\ReflectionParameter $dependency) use ($id) {

                if (is_null($dependency->getClass())) {
                    throw NotFoundException::create($id);
                }

                return $this->get($dependency->getClass()->getName());

            },
            $constructor->getParameters()
        );

        return $reflector->newInstanceArgs($dependencies);
    }

    public function has($id)
    {
        return isset($this->definitions[$id]);
    }

    /**
     * Adds an entry to the container.
     *
     * @param string   $id       Identifier of the entry.
     * @param \Closure $value    The closure to invoke when this entry is resolved.
     *                           The closure will be given this container as the only
     *                           argument when invoked.
     */
    public function set($id, \Closure $value)
    {
        $this->definitions[$id] = function ($container) use ($value) {
            static $object;

            if (is_null($object)) {
                $object = $value($container);
            }

            return $object;
        };
    }

    /**
     * Adds a shared (singleton) entry to the container.
     *
     * @param string   $id       Identifier of the entry.
     * @param \Closure $value    The closure to invoke when this entry is resolved.
     */
    public function share($id, \Closure $value)
    {
        $this->definitions[$id] = function ($container) use ($value) {

            static $resolvedValue;

            if (is_null($resolvedValue)) {
                $resolvedValue = $value($container);
            }

            return $resolvedValue;
        };
    }
}
