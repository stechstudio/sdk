<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 3/21/16
 * Time: 8:56 PM
 */

namespace STS\Sdk\Breaker;

/**
 * Class CircuitBreaker
 * @package STS\Sdk
 */
/**
 * Class BreakerSwitch
 * @package STS\Sdk\Breaker
 */
class BreakerSwitch
{
    /**
     *
     */
    const CLOSED = 2;
    /**
     *
     */
    const HALF_OPEN = 1;
    /**
     *
     */
    const OPEN = 0;

    /**
     * @var string
     */
    protected $serviceName;

    /**
     * @var int
     */
    protected $state;

    /**
     * @var int
     */
    protected $maxFailures = 5;

    /**
     * @var int
     */
    protected $failures = 0;

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
    public function __construct($name)
    {
        $this->setName($name);
        $this->tripHandler = function () {};
        $this->failureHandler = function () {};
    }

    /**
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->serviceName($name);
        return $this;
    }

    /**
     * @return mixed
     */
    public function name()
    {
        return $this->name;
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
     * @return bool
     */
    public function isClosed()
    {
        return $this->state == self::CLOSED;
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->isClosed();
    }

    /**
     * @return $this
     */
    public function trip()
    {
        $this->state = self::OPEN;

        $this->tripHandler($this);

        return $this;
    }

    /**
     * @return BreakerSwitch
     */
    public function failure()
    {
        if($this->failures >= $this->maxFailures) {
            return $this->trip();
        } else {
            $this->failures++;
            $this->state = self::HALF_OPEN;
        }

        $this->failureHandler($this);
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->state = self::CLOSED;

        return $this;
    }

    /**
     * @param $state
     *
     * @return $this
     */
    public function setState($state)
    {
        if(in_array($state, [self::CLOSED, self::HALF_OPEN, self::OPEN])) {
            $this->state = $state;
        }

        return $this;
    }
}