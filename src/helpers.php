<?php
use Illuminate\Container\Container;
if (! function_exists('build')) {
    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     *
     * @return mixed
     */
    function build($abstract)
    {
        return container()->make($abstract);
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