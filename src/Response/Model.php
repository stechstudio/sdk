<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 4/1/16
 * Time: 4:38 PM
 */

namespace STS\Sdk\Response;

use ArrayAccess;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use JsonSerializable;

/**
 * Class Model
 * @package STS\Sdk\Response
 */
abstract class Model implements ArrayAccess, Arrayable, Jsonable, JsonSerializable
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $relatedModels = [];

    /**
     * @var array
     */
    protected $relations = [];

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
        if(array_key_exists($key, $this->relatedModels)) {
            return $this->getRelated($key);
        }

        if($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, Arr::get($this->attributes, $key));
        }

        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return null;
    }

    /**
     * @param $key
     *
     * @return Model
     */
    protected function getRelated($key)
    {
        if(!array_key_exists($key, $this->relations)) {
            $class = $this->relatedModels[$key];
            $data = (array) Arr::get($this->attributes, $key);
            $this->relations[$key] = new $class($data);
        }

        return $this->relations[$key];
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    public function __set($key, $value)
    {
        if ($this->hasSetMutator($key)) {
            $method = 'set'.Str::studly($key).'Attribute';

            return $this->{$method}($value);
        }

        $this->attributes[$key] = $value;
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
     *
     * @return bool
     */
    protected function hasSetMutator($key)
    {
        return method_exists($this, 'set'.Str::studly($key).'Attribute');
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

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->attributes) || array_key_exists($offset, $this->relatedModels) || $this->hasGetMutator($offset);
    }

    /**
     * @param mixed $offset
     *
     * @return null
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        if(array_key_exists($offset, $this->attributes)) {
            unset($this->attributes[$offset]);
        }

        if(array_key_exists($offset, $this->relatedModels)) {
            unset($this->relatedModels[$offset]);
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }
}