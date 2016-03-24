<?php
namespace STS\Sdk\CircuitBreaker;

use Stash\Item;
use Stash\Pool;
use STS\Sdk\CircuitBreaker;

/**
 * Class Cache
 * @package STS\Sdk\CircuitBreaker
 */
class Cache
{
    /**
     * @var Pool
     */
    protected $pool;

    /**
     * Cache constructor.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->setPool($pool);
    }

    /**
     * Load from cache and initialize the breaker
     *
     * @param CircuitBreaker $breaker
     */
    public function load(CircuitBreaker $breaker)
    {
        $data = $this->getItem($breaker)->get();

        if(!is_array($data)) {
            return;
        }

        if(array_key_exists('state', $data)) {
            $breaker->setState($data['state']);
        }

        if(array_key_exists('history', $data)) {
            $breaker->setHistory($data['history']);
        }

        if(array_key_exists('lastTrippedAt', $data)) {
            $breaker->setLastTrippedAt($data['lastTrippedAt']);
        }
    }

    /**
     * Save the breaker state variables to cache
     *
     * @param CircuitBreaker $breaker
     */
    public function save(CircuitBreaker $breaker)
    {
        $item = $this->getItem($breaker);

        $item->set($breaker->toArray());
        $this->getPool()->save($item);
    }

    /**
     * @param CircuitBreaker $breaker
     *
     * @return \Stash\Interfaces\ItemInterface|Item
     */
    public function getItem(CircuitBreaker $breaker)
    {
        return $this->getPool()->getItem($this->getCacheKey($breaker));
    }

    /**
     * @param CircuitBreaker $breaker
     *
     * @return string
     */
    public function getCacheKey(CircuitBreaker $breaker)
    {
        return "Sdk/CircuitBreaker/" . $breaker->getName();
    }

    /**
     * @return Pool
     */
    public function getPool()
    {
        return $this->pool;
    }

    /**
     * @param Pool $pool
     */
    public function setPool($pool)
    {
        $this->pool = $pool;
    }
}
