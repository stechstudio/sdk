<?php
use Illuminate\Container\Container;

if (! function_exists('make')) {
    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @param array $parameters
     *
     * @return mixed
     */
    function make($abstract, array $parameters = [])
    {
        return container()->make($abstract, $parameters);
    }
}

if (! function_exists('container')) {
    /**
     * Return current container, initialize if need be
     *
     * @return Container
     */
    function container()
    {
        if(is_null(Container::getInstance())) {
            $container = new Container();
            $container->instance('Illuminate\Contracts\Container\Container', $container);
            Container::setInstance($container);
        }

        return Container::getInstance();
    }
}

if (! function_exists('is_not_null')) {
    /**
     * Reverse of is_null. Really useful with array_filter.
     *
     * @return boolean
     */
    function is_not_null($value)
    {
        return !is_null($value);
    }
}
