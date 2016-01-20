<?php
use Illuminate\Container\Container;

if (! function_exists('container')) {
    /**
     * Get the available container instance... and create a new one if needed!
     *
     * @return Illuminate\Container\Container
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