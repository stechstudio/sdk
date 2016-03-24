<?php
namespace STS\Sdk;

use Exception;
use Stash\Driver\Ephemeral;
use Stash\Pool;
use STS\Sdk\CircuitBreaker\Cache;

class CircuitBreakerTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiateAndDefaults()
    {
        $breaker = container()->make(CircuitBreaker::class)->setName("Foo");

        $this->assertTrue($breaker instanceof CircuitBreaker);

        $this->assertEquals("Foo", $breaker->getName());
    }

    /**
     * The flow was designed to mostly follow the diagram at https://msdn.microsoft.com/en-us/library/dn589784.aspx
     */
    public function testStateFlow()
    {
        $breaker = container()->make(CircuitBreaker::class)->setName("Foo")->setAutoRetryInterval(1);

        // We start off closed by default
        $this->assertEquals($breaker->getState(), CircuitBreaker::CLOSED);

        // A single failure should leave use closed
        $breaker->failure();
        $this->assertEquals($breaker->getState(), CircuitBreaker::CLOSED);

        // Now go fail enough times to reash the threshold
        for($i = 0; $i < $breaker->getFailureThreshold(); $i++) {
            $breaker->failure();
        }

        // Breaker has tripped
        $this->assertEquals($breaker->getState(), CircuitBreaker::OPEN);

        // Sleep long enough for the breaker to retry
        sleep($breaker->getAutoRetryInterval());

        // We are back to half-open
        $this->assertEquals($breaker->getState(), CircuitBreaker::HALF_OPEN);

        // One failure should trip us at this point
        $breaker->failure();
        $this->assertEquals($breaker->getState(), CircuitBreaker::OPEN);

        // Sleep again
        sleep($breaker->getAutoRetryInterval());

        // Succeed enough to snap closed
        for($i = 0; $i < $breaker->getSuccessThreshold(); $i++) {
            $breaker->success();
        }

        // And we're good!
        $this->assertEquals($breaker->getState(), CircuitBreaker::CLOSED);
    }

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

        $breaker = container()->make(CircuitBreaker::class)->setName("Foo")->setCache($cache);

        $this->assertTrue($breaker->isAvailable());
        $this->assertFalse($breaker->isClosed());
        $this->assertEquals(1, $breaker->getFailures());
        $this->assertEquals(2, $breaker->getSuccesses());
        $this->assertEquals($array, $breaker->toArray());
    }

    public function testCacheIsUpdated()
    {
        $breaker = container()->make(CircuitBreaker::class)->setName("Foo")->setAutoRetryInterval(0);
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

    public function testHandlers()
    {
        $counter = 0;

        $breaker = container()->make(CircuitBreaker::class)->setName("Foo")
            ->registerHandler("success", function($event, $breaker) use(&$counter) { $counter++; })
            ->registerHandler("failure", function($event, $breaker) use(&$counter) { $counter++; })
            ->registerHandler("trip", function($event, $breaker) use(&$counter) { $counter++; })
            ->registerHandler("reset", function($event, $breaker) use(&$counter) { $counter++; });

        $breaker->success();
        $breaker->failure();
        $breaker->trip();
        $breaker->reset();

        $this->assertEquals(4, $counter);
    }

    public function testFailureInterval()
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

        $breaker = container()->make(CircuitBreaker::class)->setName("Foo")->setCache($cache)->setFailureThreshold(3)->setFailureInterval(1);

        // Ok, so we want to fail three times and ensure the breaker is tripped
        $breaker->failure(); $breaker->failure(); $breaker->failure();

        $this->assertFalse($breaker->isAvailable());

        // Awesome. Now let's reset.
        $breaker->reset();

        // Fail twice.
        $breaker->failure(); $breaker->failure();
        $this->assertEquals(2, $breaker->getFailures());

        // And now sleep for two seconds before failing the third time. Note we set our failure interval above to 1, so we shouldn't trip.
        sleep(2);
        $breaker->failure();

        // And we should still be closed.
        $this->assertTrue($breaker->isAvailable());
        $this->assertEquals(1, $breaker->getFailures());
    }

    public function testLoadConfig()
    {
        $counter = 0;

        $config = [
            'failureThreshold' => 2,
            'successThreshold' => 3,
            'autoRetryInterval' => 1,
            'failureInterval' => 4,
            'handlers' => [
                "success" => function($event, $breaker) use(&$counter) { $counter++; },
                "failure" => function($event, $breaker) use(&$counter) { $counter++; },
                "trip" => function($event, $breaker) use(&$counter) { $counter++; },
                "reset" => function($event, $breaker) use(&$counter) { $counter++; },
            ]
        ];

        $breaker = container()->make(CircuitBreaker::class)->setName("Foo")->loadConfig($config);

        $this->assertEquals($config['failureThreshold'], $breaker->getFailureThreshold());
        $this->assertEquals($config['successThreshold'], $breaker->getSuccessThreshold());
        $this->assertEquals($config['autoRetryInterval'], $breaker->getAutoRetryInterval());
        $this->assertEquals($config['failureInterval'], $breaker->getFailureInterval());

        $breaker->success();
        $breaker->failure();
        $breaker->trip();
        $breaker->reset();

        $this->assertEquals($counter, 4);
    }

    public function testClassInvokeHandler()
    {
        $breaker = container()->make(CircuitBreaker::class)->setName("Foo")
            ->loadConfig([
                'handlers' => [
                    "failure" => EventHandler::class
                ]
            ]);

        $this->setExpectedException(Exception::class, "Got event [failure] for breaker [Foo]");
        $breaker->failure();
    }
}


class EventHandler {
    public function __invoke($event, $breaker)
    {
        throw new Exception("Got event [$event] for breaker [{$breaker->getName()}]");
    }
}
