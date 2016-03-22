<?php
namespace STS\Sdk\CircuitBreaker;

use Stash\Pool;
use Stash\Item;

/**
 * Class SwitchCache
 * @package STS\Sdk\CircuitBreaker
 */
class SwitchCache
{
    /**
     * @var string
     */
    protected $serviceName;
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
    protected $cacheData;
    /**
     * @var int
     */
    protected $maxHistory = 100;

    /**
     * SwitchCache constructor.
     *
     * @param Pool $cachePool
     * @param      $serviceName
     */
    public function __construct(Pool $cachePool, $serviceName)
    {
        $this->serviceName = $serviceName;
        $this->cachePool = $cachePool;
        $this->cacheItem = $this->cachePool->get("sdk/$serviceName");

        if ($this->cacheItem->isMiss()) {
            $this->initializeCacheItem();
        }

        $this->cacheData = $this->cacheItem->get();
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->cacheData['state'];
    }

    /**
     * @return mixed
     */
    public function getFailures()
    {
        return $this->cacheData['failures'];
    }

    /**
     *
     */
    public function recordSuccess()
    {
        $this->addToHistory("success");
    }

    /**
     *
     */
    public function recordFailure()
    {
        $this->cacheData['failures']++;

        $this->addToHistory("failure");
    }

    /**
     * @param $event
     */
    protected function addToHistory($event)
    {
        array_unshift($this->cacheData['history'], [
            'timestamp' => gmdate('c'),
            'event' => $event
        ]);

        if(count($this->cacheData['history']) >= $this->maxHistory) {
            array_pop($this->cacheData['history']);
        }

        $this->save();
    }

    /**
     *
     */
    public function save()
    {
        $this->cacheItem->set($this->cacheData);
        $this->cachePool->save($this->cacheItem);
    }

    public function reset()
    {
        $this->cacheData['failures'] = 0;
        $this->cacheData['state'] = BreakerSwitch::CLOSED;

        $this->save();
    }

    /**
     *
     */
    protected function initializeCacheItem()
    {
        $this->cacheItem->set([
            'state' => BreakerSwitch::CLOSED,
            'failures' => 0,
            'history' => []
        ]);
    }
}
