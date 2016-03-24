<?php
namespace STS\Sdk;

use Exception;
use Stash\Driver\Ephemeral;
use Stash\Pool;

class CircuitBreakerTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiateAndDefaults()
    {
        $breaker = new CircuitBreaker("Foo");

        $this->assertTrue($breaker instanceof CircuitBreaker);

        $this->assertEquals("Foo", $breaker->getName());
        $this->assertEquals("Sdk/CircuitBreaker/Foo", $breaker->getCacheKey());
    }

    /**
     * The flow was designed to mostly follow the diagram at https://msdn.microsoft.com/en-us/library/dn589784.aspx
     */
    public function testStateFlow()
    {
        $breaker = (new CircuitBreaker("Foo"))->setAutoRetryInterval(1);

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
        $cache = new Pool(new Ephemeral());
        $item = $cache->getItem('Sdk/CircuitBreaker/Foo');

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
        $cache->save($item);

        $breaker = (new CircuitBreaker("Foo"))->setCachePool($cache);

        $this->assertTrue($breaker->isAvailable());
        $this->assertFalse($breaker->isClosed());
        $this->assertEquals(1, $breaker->getFailures());
        $this->assertEquals(2, $breaker->getSuccesses());
        $this->assertEquals($array, $breaker->toArray());
    }

    public function testCacheIsUpdated()
    {
        $cache = new Pool(new Ephemeral());

        $breaker = (new CircuitBreaker("Foo"))->setCachePool($cache)->setAutoRetryInterval(0);

        $breaker->failure();
        $this->assertEquals(1, count($cache->getItem('Sdk/CircuitBreaker/Foo')->get()['history']['failure']));
        $this->assertFalse(array_key_exists("success", $cache->getItem('Sdk/CircuitBreaker/Foo')->get()['history']));

        // Success events are NOT tracked when the breaker is closed
        $breaker->success();
        $this->assertFalse(array_key_exists("success", $cache->getItem('Sdk/CircuitBreaker/Foo')->get()['history']));

        $breaker->trip();
        $this->assertEquals(CircuitBreaker::OPEN, $cache->getItem('Sdk/CircuitBreaker/Foo')->get()['state']);

        // Because we set autoRetry to 0, the breaker will be half-open on the next check. And success events tracked.

        $breaker->success();
        $this->assertEquals(CircuitBreaker::HALF_OPEN, $cache->getItem('Sdk/CircuitBreaker/Foo')->get()['state']);
        $this->assertEquals(1, count($cache->getItem('Sdk/CircuitBreaker/Foo')->get()['history']['success']));
    }

    public function testHandlers()
    {
        $counter = 0;

        $breaker = (new CircuitBreaker("Foo"))
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
        $cache = new Pool(new Ephemeral());
        $item = $cache->getItem('Sdk/CircuitBreaker/Foo');

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
        $cache->save($item);

        $breaker = (new CircuitBreaker("Foo"))->setCachePool($cache)->setFailureThreshold(3)->setFailureInterval(1);

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
            'handlers' => [
                "success" => function($event, $breaker) use(&$counter) { $counter++; },
                "failure" => function($event, $breaker) use(&$counter) { $counter++; },
                "trip" => function($event, $breaker) use(&$counter) { $counter++; },
                "reset" => function($event, $breaker) use(&$counter) { $counter++; },
            ]
        ];

        $breaker = (new CircuitBreaker("Foo"))->loadConfig($config);

        $this->assertEquals($config['failureThreshold'], $breaker->getFailureThreshold());
        $this->assertEquals($config['successThreshold'], $breaker->getSuccessThreshold());
        $this->assertEquals($config['autoRetryInterval'], $breaker->getAutoRetryInterval());

        $breaker->success();
        $breaker->failure();
        $breaker->trip();
        $breaker->reset();

        $this->assertEquals($counter, 4);
    }

    public function testClassInvokeHandler()
    {
        $breaker = (new CircuitBreaker("Foo"))
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
