<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 4/1/16
 * Time: 4:38 PM
 */

namespace STS\Sdk\Response;

/**
 * Class Model
 * @package STS\Sdk\Response
 */
use Illuminate\Support\Str;

/**
 * Class Model
 * @package STS\Sdk\Response
 */
abstract class Model
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    /**
     * @param $key
     *
     * @return null
     */
    public function __get($key)
    {
        if($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, array_get($this->attributes, $key));
        }

        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $key .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    protected function hasGetMutator($key)
    {
        return method_exists($this, 'get'.Str::studly($key).'Attribute');
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        return $this->{'get'.Str::studly($key).'Attribute'}($value);
    }
}