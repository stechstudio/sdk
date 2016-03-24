<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 3/23/16
 * Time: 8:26 AM
 */

namespace STS\Sdk;

use DateTime;
use Illuminate\Contracts\Support\Arrayable;
use Stash\Item;
use Stash\Pool;

/**
 * Class CircuitBreaker
 * @package STS\Sdk
 */
class CircuitBreaker implements Arrayable
{
    /**
     * Service is fully operational
     */
    const CLOSED = 2;
    /**
     * Service has had some failures, and breaker will trip once we reach $failureThreshold
     */
    const HALF_OPEN = 1;
    /**
     * Breaker has tripped, service is unavailable. We will retry after $autoRetryInterval
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
     * In the Closed state, this is the number of failures (within the $failureInterval)
     * that will trip the breaker and go to Open
     *
     * @var int
     */
    protected $failureThreshold = 10;

    /**
     * @var int
     */
    protected $failureInterval = 300;

    /**
     * Once in the Half-Open state, this is the number of success at which point
     * the circuit breaker will reset back to Closed
     *
     * @var int
     */
    protected $successThreshold = 5;

    /**
     * Once in the Open state, we will wait this many seconds, at which point
     * we'll automatically go back to Half-Open for retries
     *
     * @var int
     */
    protected $autoRetryInterval = 300;

    /**
     * @var DateTime
     */
    protected $lastTrippedAt;

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
     * @var array
     */
    protected $handlers = [];

    /**
     * @param null $name
     */
    public function __construct($name = null)
    {
        if ($name != null) {
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
        $this->setCacheKey("Sdk/CircuitBreaker/$name");

        return $this;
    }

    /**
     * @param $cacheKey
     *
     * @return $this
     */
    public function setCacheKey($cacheKey)
    {
        $this->cacheKey = $cacheKey;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCacheKey()
    {
        return $this->cacheKey;
    }

    /**
     * @param $state
     *
     * @return $this
     */
    public function setState($state)
    {
        if (!in_array($state, [self::CLOSED, self::HALF_OPEN, self::OPEN])) {
            throw new \InvalidArgumentException("Invalid circuit breaker state [$state]");
        }

        // If state is changing, clear history
        if ($state != $this->state) {
            $this->resetHistory();
        }

        $this->state = $state;

        return $this;
    }

    /**
     * @return int
     */
    public function getState()
    {
        if ($this->state == self::OPEN) {
            $this->checkAutoRetryInterval();
        }

        return $this->state;
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
     * @return int
     */
    public function getFailureInterval()
    {
        return $this->failureInterval;
    }

    /**
     * @param int $failureInterval
     *
     * @return $this
     */
    public function setFailureInterval($failureInterval)
    {
        $this->failureInterval = $failureInterval;

        return $this;
    }

    /**
     * @param $autoRetryInterval
     *
     * @return $this
     */
    public function setAutoRetryInterval($autoRetryInterval)
    {
        $this->autoRetryInterval = $autoRetryInterval;

        return $this;
    }

    /**
     * @return int
     */
    public function getAutoRetryInterval()
    {
        return $this->autoRetryInterval;
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
     * @return int
     */
    public function getFailures()
    {
        return array_key_exists('failure', $this->history)
            ? $this->countEventsInInterval($this->history['failure'], $this->getFailureInterval())
            : 0;
    }

    /**
     * @return int
     */
    public function getSuccesses()
    {
        return array_key_exists('success', $this->history)
            ? count($this->history['success'])
            : 0;
    }

    /**
     * @return int
     */
    public function getFailureThreshold()
    {
        return $this->failureThreshold;
    }

    /**
     * @return int
     */
    public function getSuccessThreshold()
    {
        return $this->successThreshold;
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
        $this->handle('failure');

        if ($this->getState() == self::HALF_OPEN) {
            // Go right back to open
            $this->trip();
        }

        if ($this->failureThresholdReached()) {
            $this->trip();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function trip()
    {
        $this->setState(self::OPEN);
        $this->lastTrippedAt = new DateTime();

        $this->handle('trip');

        return $this;
    }

    /**
     * @return $this
     */
    public function success()
    {
        $this->handle('success');

        if ($this->successThresholdReached()) {
            $this->reset();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->setState(self::CLOSED);

        $this->handle('reset');

        return $this;
    }

    /**
     *
     */
    protected function checkAutoRetryInterval()
    {
        $current = new DateTime();
        $diff = $current->diff($this->lastTrippedAt);

        if ($diff->s >= $this->getAutoRetryInterval()) {
            $this->setState(self::HALF_OPEN);
        }
    }

    /**
     * @param          $event
     * @param callable $handler
     *
     * @return $this
     */
    public function registerHandler($event, callable $handler)
    {
        if(!array_key_exists($event, $this->handlers)) {
            $this->handlers[$event] = [];
        }

        $this->handlers[$event][] = $handler;

        return $this;
    }

    /**
     * @param $event
     */
    protected function handle($event)
    {
        // These are the events we specifically need to track in our history
        if ($this->getState() == self::CLOSED && $event == "failure" || $this->getState() == self::HALF_OPEN && $event == "success") {
            $this->addToHistory($event);
        }

        if (!array_key_exists($event, $this->handlers) || !is_array($this->handlers[$event])) {
            return;
        }

        foreach($this->handlers[$event] AS $handler) {
            call_user_func($handler, $event, $this);
        }
    }

    /**
     * @param $event
     */
    protected function addToHistory($event)
    {
        if (!array_key_exists($event, $this->history)) {
            $this->history[$event] = [];
        }

        array_push($this->history[$event], new DateTime());

        $this->save();
    }

    protected function resetHistory()
    {
        $this->history = [];
        $this->save();
    }

    /**
     * @return bool
     */
    protected function failureThresholdReached()
    {
        return $this->state == self::CLOSED && $this->getFailures() >= $this->getFailureThreshold();
    }

    /**
     * @return bool
     */
    protected function successThresholdReached()
    {
        return $this->getState() == self::HALF_OPEN && $this->getSuccesses() >= $this->getSuccessThreshold();
    }

    /**
     * @param $array
     * @param $interval
     *
     * @return int
     */
    protected function countEventsInInterval($array, $interval)
    {
        $current = new DateTime();

        // First remove any array items that are outside the interval
        foreach ($array AS $key => $dateTime) {
            if ($current->diff($dateTime)->s > $interval) {
                unset($array[$key]);
            }
        }

        return count($array);
    }

    /**
     * Initialize the circuit breaker data from cache
     */
    protected function loadFromCache()
    {
        if (!$this->getCacheKey()) {
            throw new \InvalidArgumentException("You must specify a name/cacheKey before setting cache");
        }

        $this->cacheItem = $this->cachePool->getItem($this->getCacheKey());

        if ($this->cacheItem->isHit()) {
            $data = $this->cacheItem->get();

            if (isset($data['state'])) {
                $this->state = $data['state'];
            }
            if (isset($data['history'])) {
                $this->history = $data['history'];
            }
            if (isset($data['lastTrippedAt'])) {
                $this->lastTrippedAt = $data['lastTrippedAt'];
            }
        }
    }

    /**
     * Saves our circuit breaker data to cache
     */
    protected function save()
    {
        if (!$this->cacheItem) {
            // No cache setup. Just going to silently fail here, in case we purposefully
            // didn't want this breaker to be cache-backed. Should this be an exception
            // instead?

            return;
        }

        $this->cacheItem->set($this->toArray());
        $this->cachePool->save($this->cacheItem);
    }

    /**
     * Helpful for quickloading an array of circuit breaker options from a description file or other config file
     *
     * @param array $config
     *
     * @return $this
     */
    public function loadConfig(array $config)
    {
        foreach (['failureThreshold', 'successThreshold', 'autoRetryInterval'] AS $attribute) {
            if (isset($config[$attribute])) {
                $setter = "set" . ucwords($attribute);
                $this->{$setter}($config[$attribute]);
            }
        }

        if (isset($config['handlers']) && is_array($config['handlers'])) {
            foreach ($config['handlers'] AS $event => $handler) {
                $this->registerHandler($event, $this->getCallable($handler));
            }
        }

        return $this;
    }

    /**
     * @param $item
     *
     * @return mixed
     */
    protected function getCallable($item)
    {
        if (is_string($item) && class_exists($item, true)) {
            $item = container()->make($item);
        }

        if (is_callable($item)) {
            return $item;
        }

        throw new \InvalidArgumentException("Not a valid callable [$item]");
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'state' => $this->state,
            'history' => $this->history,
            'lastTrippedAt' => $this->lastTrippedAt
        ];
    }
}
