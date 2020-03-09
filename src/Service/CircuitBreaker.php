<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 3/23/16
 * Time: 8:26 AM
 */

namespace STS\Sdk\Service;

use DateTime;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use STS\Sdk\CircuitBreaker\Cache;
use STS\Sdk\CircuitBreaker\History;
use STS\Sdk\CircuitBreaker\Monitor;

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
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $state = 2;

    /**
     * In the Closed state, this is the number of failures (within the $failureInterval)
     * that will trip the breaker and go to Open
     *
     * @var int
     */
    protected $failureThreshold = 10;

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
     * @var History
     */
    protected $history;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var Monitor
     */
    protected $monitor;

    /**
     * @param string $name
     * @param array  $config
     */
    public function __construct($name, $config = [])
    {
        $this->name = $name;
        $this->loadConfig($config);
    }

    /**
     * @param array $config
     */
    public function loadConfig(array $config)
    {
        foreach (['failureThreshold', 'failureInterval', 'successThreshold', 'autoRetryInterval'] AS $attribute) {
            if (isset($config[$attribute])) {
                $setter = "set" . ucwords($attribute);
                $this->{$setter}($config[$attribute]);
            }
        }

        foreach ((array)Arr::get($config, 'handlers') AS $event => $handler) {
            $this->registerCallback($event, $handler);
        }
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

        return $this;
    }

    /**
     * @param Cache $cache
     *
     * @return $this
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;

        if ($this->name != null) {
            $this->cache->load($this);
        }

        return $this;
    }

    /**
     * @return Cache
     */
    public function getCache()
    {
        if (!$this->cache) {
            $this->cache = new Cache();
        }

        return $this->cache;
    }

    /**
     * @return Monitor
     */
    public function getMonitor()
    {
        if (!$this->monitor) {
            $this->monitor = new Monitor();
        }

        return $this->monitor;
    }

    /**
     * @param $state
     *
     * @return $this
     */
    public function setState($state)
    {
        if (!in_array($state, [self::CLOSED, self::HALF_OPEN, self::OPEN], true)) {
            return $this;
        }

        $oldState = $this->state;
        $this->state = $state;

        // If state just changed, clear history
        if ($state != $oldState) {
            $this->getHistory()->clear();
            $this->save();
        }

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
        return $this->history->getFailureInterval();
    }

    /**
     * @param int $failureInterval
     *
     * @return $this
     */
    public function setFailureInterval($failureInterval)
    {
        $this->history->setFailureInterval($failureInterval);

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
     * @return int
     */
    public function getFailures()
    {
        return $this->getHistory()->failures();
    }

    /**
     * @return int
     */
    public function getSuccesses()
    {
        return $this->getHistory()->successes();
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
     * @return History
     */
    public function getHistory()
    {
        if (!$this->history) {
            $this->history = new History();
        }

        return $this->history;
    }

    /**
     * @return DateTime
     */
    public function getLastTrippedAt()
    {
        if (!$this->lastTrippedAt instanceof DateTime) {
            // No valid lastTrippedAt, set it to now
            $this->setLastTrippedAt(new DateTime());
        }

        return $this->lastTrippedAt;
    }

    /**
     * @param DateTime $lastTrippedAt
     *
     * @return $this
     */
    public function setLastTrippedAt($lastTrippedAt)
    {
        $this->lastTrippedAt = $lastTrippedAt;

        return $this;
    }

    /**
     * @return bool
     */
    public function isClosed()
    {
        return $this->getState() == self::CLOSED;
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->getState() == self::CLOSED || $this->getState() == self::HALF_OPEN;
    }

    /**
     * @param null $context
     *
     * @return $this
     */
    public function failure($context = null)
    {
        $this->handle('failure', $context);

        if ($this->getState() == self::HALF_OPEN || $this->failureThresholdReached()) {
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
        $this->setLastTrippedAt(new DateTime());

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
        $diff = time() - $this->getLastTrippedAt()->getTimestamp();

        if ($diff >= $this->getAutoRetryInterval()) {
            $this->setState(self::HALF_OPEN);
        }
    }

    /**
     * @param string $event
     * @param mixed  $callback
     *
     * @return $this
     */
    public function registerCallback($event, $callback)
    {
        $this->getMonitor()->on($event, $callback);

        return $this;
    }

    /**
     * @param      $event
     * @param null $context
     */
    protected function handle($event, $context = null)
    {
        // These are the events we specifically need to track in our history
        if ($this->getState() == self::CLOSED && $event == "failure" || $this->getState() == self::HALF_OPEN && $event == "success") {
            $this->getHistory()->add($event);
            $this->save();
        }

        if ($event == "trip" || $event == "reset") {
            $this->save();
        }

        $this->getMonitor()->handle($event, $this, $context);
    }

    /**
     * @return bool
     */
    protected function failureThresholdReached()
    {
        return $this->getState() == self::CLOSED && $this->getHistory()->failures() >= $this->getFailureThreshold();
    }

    /**
     * @return bool
     */
    protected function successThresholdReached()
    {
        return $this->getState() == self::HALF_OPEN && $this->getHistory()->successes() >= $this->getSuccessThreshold();
    }

    /**
     * Saves our circuit breaker data to cache
     */
    protected function save()
    {
        $this->getCache()->save($this);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'state' => $this->state,
            'history' => $this->history->toArray(),
            'lastTrippedAt' => $this->lastTrippedAt
        ];
    }
}
