<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 3/23/16
 * Time: 8:26 AM
 */

namespace STS\Sdk;

use Stash\Item;
use Stash\Pool;

/**
 * Class CircuitBreaker
 * @package STS\Sdk
 */
class CircuitBreaker
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
     * @var
     */
    protected $name;

    /**
     * @var
     */
    protected $cacheKey;

    /**
     * @var int
     */
    protected $state;

    /**
     * @var int
     */
    protected $failures = 0;

    /**
     * @var int
     */
    protected $failureThreshold = 10;

    /**
     * @var array
     */
    protected $history = [];

    /**
     * @var Pool
     */
    protected $cachePool;

    /**
     * @var Item
     */
    protected $cacheItem;

    /**
     * @var int
     */
    protected $timeout = 120;

    /**
     * @var array
     */
    protected $handlers = [];

    /**
     * @param null $name
     */
    public function __construct($name = null)
    {
        if($name != null) {
            $this->setName($name);
        }

        $this->state = self::CLOSED;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        $this->cacheKey = "sdk/$name";

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

    /**
     * @param $cachePool
     *
     * @return $this
     */
    public function setCachePool($cachePool)
    {
        $this->cachePool = $cachePool;
        $this->loadFromCache();

        return $this;
    }

    /**
     * @param $failureThreshold
     *
     * @return $this
     */
    public function setFailureThreshold($failureThreshold)
    {
        $this->failureThreshold = $failureThreshold;

        return $this;
    }

    /**
     * @param $successThreshold
     *
     * @return $this
     */
    public function setSuccessThreshold($successThreshold)
    {
        $this->successThreshold = $successThreshold;

        return $this;
    }

    /**
     * @param $timeout
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

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
        return $this->state == self::CLOSED || $this->state == self::HALF_OPEN;
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

        $this->handle('failure');

        if ($this->failures >= $this->failureThreshold) {
            $this->trip();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function trip()
    {
        $this->state = self::OPEN;
        $this->handle('trip');

        return $this;
    }

    /**
     * @return $this
     */
    public function success()
    {
        $this->handle('success');

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
        $this->failures = 0;

        $this->handle('reset');

        return $this;
    }

    /**
     * @param          $event
     * @param callable $handler
     */
    public function registerHandler($event, callable $handler)
    {
        $this->handlers[$event] = $handler;
    }

    /**
     * @param $event
     */
    protected function handle($event)
    {
        if(array_key_exists($event, $this->handlers) && is_callable($this->handlers[$event])) {
            call_user_func($this->handlers[$event], $this);
        }
    }

    /**
     *
     */
    protected function loadFromCache()
    {
        if(!$this->cacheKey) {
            throw new \InvalidArgumentException("You must specify a name before setting cache");
        }

        $this->cacheItem = $this->cachePool->get($this->cacheKey);

        if($this->cacheItem->isHit()) {
            $data = $this->cacheItem->get();

            $this->failures = $data['failures'];
            $this->state = $data['state'];
            $this->history = $data['history'];
        }
    }

    /**
     *
     */
    protected function save()
    {
        $this->cacheItem->set([
            'failures' => $this->failures,
            'state' => $this->state,
            'history' => $this->history
        ]);

        $this->cachePool->save($this->cacheItem);
    }

    /**
     * @param array $config
     *
     * @return $this
     */
    public function loadConfig(array $config)
    {


        return $this;
    }
}