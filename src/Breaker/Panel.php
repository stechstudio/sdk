<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 3/21/16
 * Time: 9:36 PM
 */

namespace STS\Sdk\Breaker;


use Stash\Pool;

/**
 * Class Panel
 * @package STS\Sdk\Breaker
 */
class Panel
{
    /**
     * @var array
     */
    protected $switches = [];

    /**
     * @var
     */
    protected $cachePool;

    /**
     * @var int
     */
    protected $maxFailures = 5;

    /**
     * @var callable
     */
    protected $tripHandler;

    /**
     * @var callable
     */
    protected $failureHandler;

    /**
     *
     */
    public function __construct()
    {
        $this->cachePool = new Pool();
        $this->tripHandler = function () {};
        $this->failureHandler = function () {};
    }

    /**
     * @param Pool $pool
     *
     * @return $this
     */
    public function setCachePool(Pool $pool)
    {
        $this->cachePool = $pool;

        return $this;
    }

    /**
     * @param $max
     *
     * @return $this
     */
    public function setMaxFailures($max)
    {
        $this->maxFailures = $max;

        return $this;
    }

    /**
     * @param callable $handler
     *
     * @return $this
     */
    public function onTrip(callable $handler)
    {
        $this->tripHandler = $handler;

        return $this;
    }

    /**
     * @param callable $handler
     *
     * @return $this
     */
    public function onFailure(callable $handler)
    {
        $this->failureHandler = $handler;

        return $this;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function get($name)
    {
        if (!array_key_exists($name, $this->switches)) {
            $this->switches[$name] = $this->initialize($name);
        }

        return $this->switches[$name];
    }

    /**
     * @param $name
     *
     * @return BreakerSwitch
     */
    protected function initialize($name)
    {
        $switch = (new BreakerSwitch($name))
            ->setState(BreakerSwitch::CLOSED)
            ->setMaxFailures($this->maxFailures)
            ->onTrip($this->tripHandler)
            ->onFailure($this->failureHandler);

        return $switch;
    }
}