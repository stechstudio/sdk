<?php
namespace STS\Sdk\CircuitBreaker;

use Stash\Driver\Ephemeral;
use Stash\Pool;
use STS\Sdk\CircuitBreaker;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadFromCache()
    {
        $pool = new Pool(new Ephemeral());
        $item = $pool->getItem('Sdk/CircuitBreaker/Foo');

        $array = [
            'state' => CircuitBreaker::HALF_OPEN,
            'history' => [
                'failure' => [
                    new \DateTime()
                ],
                'success' => [
                    new \DateTime(), new \DateTime()
                ]
            ],
            'lastTrippedAt' => null
        ];

        $item->set($array);
        $pool->save($item);
        $cache = new Cache($pool);

        $breaker = make(CircuitBreaker::class)->setName("Foo")->setCache($cache);

        $this->assertTrue($breaker->isAvailable());
        $this->assertFalse($breaker->isClosed());
        $this->assertEquals(1, $breaker->getFailures());
        $this->assertEquals(2, $breaker->getSuccesses());
        $this->assertEquals($array, $breaker->toArray());
    }

    public function testCacheIsUpdated()
    {
        $breaker = make(CircuitBreaker::class)->setName("Foo")->setAutoRetryInterval(0);
        $cache = $breaker->getCache();

        $breaker->failure();
        $this->assertEquals(1, count($cache->getItem($breaker)->get()['history']['failure']));
        $this->assertFalse(array_key_exists("success", $cache->getItem($breaker)->get()['history']));

        // Success events are NOT tracked when the breaker is closed
        $breaker->success();
        $this->assertFalse(array_key_exists("success", $cache->getItem($breaker)->get()['history']));

        $breaker->trip();
        $this->assertEquals(CircuitBreaker::OPEN, $cache->getItem($breaker)->get()['state']);

        // Because we set autoRetry to 0, the breaker will be half-open on the next check. And success events tracked.

        $breaker->success();
        $this->assertEquals(CircuitBreaker::HALF_OPEN, $cache->getItem($breaker)->get()['state']);
        $this->assertEquals(1, count($cache->getItem($breaker)->get()['history']['success']));
    }
}
