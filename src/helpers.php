<?php
use Illuminate\Container\Container;

if (! function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param  string  $make
     * @param  array   $parameters
     * @return mixed
     */
    function app($make = null, $parameters = [])
    {
        if (is_null($make)) {
            return container();
        }

        return container()->make($make, $parameters);
    }
}


if (! function_exists('container')) {
    /**
     * Get the available container instance... and create a new one if needed!
     *
     * @return static
     */
    function container()
    {
        if(is_null(Container::getInstance())) {
            Container::setInstance(new Container());
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