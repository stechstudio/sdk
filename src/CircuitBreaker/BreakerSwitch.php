<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 3/21/16
 * Time: 8:56 PM
 */

namespace STS\Sdk\CircuitBreaker;

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
     * @var SwitchCache
     */
    protected $cache;

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
     * @var callable
     */
    protected $successHandler;

    /**
     * @var callable
     */
    protected $resetHandler;

    /**
     * @param             $name
     * @param SwitchCache $cache
     */
    public function __construct($name, SwitchCache $cache)
    {
        $this->setName($name);
        $this->setCache($cache);
        $this->name = $name;
        $this->cache = $cache;
    }

    /**
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->serviceName = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function name()
    {
        return $this->serviceName;
    }

    /**
     * @param SwitchCache $cache
     *
     * @return $this
     */
    public function setCache(SwitchCache $cache)
    {
        $this->cache = $cache;

        $this->state = $this->cache->getState();

        $this->failures = $this->cache->getFailures();

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
     * @param callable $handler
     *
     * @return $this
     */
    public function onSuccess(callable $handler)
    {
        $this->successHandler = $handler;

        return $this;
    }

    /**
     * @param callable $handler
     *
     * @return $this
     */
    public function onReset(callable $handler)
    {
        $this->resetHandler = $handler;

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

        if (is_callable($this->tripHandler)) {
            call_user_func($this->tripHandler, $this);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function failure()
    {
        $this->failures++;

        if ($this->state == self::CLOSED) {
            $this->state = self::HALF_OPEN;
        }

        $this->cache->recordFailure();

        if (is_callable($this->failureHandler)) {
            call_user_func($this->failureHandler, $this);
        }

        if ($this->failures >= $this->maxFailures) {
            $this->trip();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function success()
    {
        $this->cache->recordSuccess();

        if (is_callable($this->successHandler)) {
            call_user_func($this->successHandler, $this);
        }

        if($this->state == self::HALF_OPEN) {
            $this->reset();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->state = self::CLOSED;

        $this->cache->reset();

        if (is_callable($this->resetHandler)) {
            call_user_func($this->resetHandler, $this);
        }

        return $this;
    }

    /**
     * @param $state
     *
     * @return $this
     */
    public function setState($state)
    {
        if (in_array($state, [self::CLOSED, self::HALF_OPEN, self::OPEN])) {
            $this->state = $state;
        }

        return $this;
    }
}
