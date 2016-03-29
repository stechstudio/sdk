<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 3/29/16
 * Time: 9:45 AM
 */
namespace STS\Sdk\Service;

use Stash\Driver\Ephemeral;
use Stash\Pool;
use STS\Sdk\CircuitBreaker\Cache;
use STS\Sdk\CircuitBreaker\Monitor;
use STS\Sdk\MyCache;

class CircuitBreakerTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiateAndDefaults()
    {
        $breaker = new CircuitBreaker("Foo");

        $this->assertTrue($breaker instanceof CircuitBreaker);

        $this->assertEquals("Foo", $breaker->getName());
    }

    /**
     * The flow was designed to mostly follow the diagram at https://msdn.microsoft.com/en-us/library/dn589784.aspx
     */
    public function testStateFlow()
    {
        $breaker = (new CircuitBreaker("Foo"))->setAutoRetryInterval(1)->setSuccessThreshold(3)->setCache(new Cache());

        // We start off closed by default
        $this->assertEquals($breaker->getState(), CircuitBreaker::CLOSED);

        // A single failure should leave use closed
        $breaker->failure();
        $this->assertEquals($breaker->getState(), CircuitBreaker::CLOSED);
        $this->assertTrue($breaker->isClosed());

        // Now go fail enough times to reach the threshold
        for ($i = 0; $i < $breaker->getFailureThreshold(); $i++) {
            $breaker->failure();
        }

        // Breaker has tripped
        $this->assertEquals($breaker->getState(), CircuitBreaker::OPEN);
        $this->assertFalse($breaker->isClosed());
        $this->assertFalse($breaker->isAvailable());

        // Sleep long enough for the breaker to retry
        sleep($breaker->getAutoRetryInterval());

        // We are back to half-open
        $this->assertEquals($breaker->getState(), CircuitBreaker::HALF_OPEN);
        $this->assertFalse($breaker->isClosed());
        $this->assertTrue($breaker->isAvailable());

        // One failure should trip us at this point
        $breaker->failure();
        $this->assertEquals($breaker->getState(), CircuitBreaker::OPEN);

        // Sleep again
        sleep($breaker->getAutoRetryInterval());

        // Succeed enough to snap closed
        for ($i = 0; $i < $breaker->getSuccessThreshold(); $i++) {
            $breaker->success();
        }

        // And we're good!
        $this->assertEquals($breaker->getState(), CircuitBreaker::CLOSED);
        $this->assertTrue($breaker->isClosed());
        $this->assertTrue($breaker->isAvailable());
    }

    public function testHandlers()
    {
        $counter = 0;

        $breaker = (new CircuitBreaker("Foo"))
            ->registerCallback("success", function ($event, $breaker) use (&$counter) {
                $counter++;
            })
            ->registerCallback("failure", function ($event, $breaker) use (&$counter) {
                $counter++;
            })
            ->registerCallback("trip", function ($event, $breaker) use (&$counter) {
                $counter++;
            })
            ->registerCallback("reset", function ($event, $breaker) use (&$counter) {
                $counter++;
            });

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
            'history' => [],
            'lastTrippedAt' => null
        ];

        $item->set($array);
        $pool->save($item);
        $cache = new Cache($pool);

        /** @var CircuitBreaker $breaker */
        $breaker = (new CircuitBreaker("Foo"))->setCache($cache)->setFailureThreshold(3)->setFailureInterval(1);

        // Ok, so we want to fail three times and ensure the breaker is tripped
        $breaker->failure();
        $breaker->failure();
        $breaker->failure();

        $this->assertFalse($breaker->isAvailable());

        // Recreate the breaker, make sure it loads from cache and is still tripped
        $breaker = (new CircuitBreaker("Foo"))->setCache($cache)->setFailureThreshold(3)->setFailureInterval(1);
        $this->assertFalse($breaker->isAvailable());

        // Awesome. Now let's reset.
        $breaker->reset();

        // Fail twice.
        $breaker->failure();
        $breaker->failure();
        $this->assertEquals(2, $breaker->getFailures());

        // And now sleep for two seconds before failing the third time. Note we set our failure interval above to 1, so we shouldn't trip.
        $this->assertEquals(1, $breaker->getFailureInterval());

        sleep(2);
        usleep(100000);

        $breaker->failure();

        // And we should still be closed.
        $this->assertTrue($breaker->isAvailable());
        $this->assertEquals(1, $breaker->getFailures());
    }

    public function testSuccesses()
    {
        /** @var CircuitBreaker $breaker */
        $breaker = (new CircuitBreaker("Foo"));

        // We don't track successes when closed
        $breaker->success();
        $this->assertEquals(0, $breaker->getSuccesses());

        // But we do when half-open
        $breaker->setState(CircuitBreaker::HALF_OPEN);
        $breaker->success();
        $breaker->success();
        $this->assertEquals(2, $breaker->getSuccesses());
    }

    public function testGetTrippedAt()
    {
        $breaker = (new CircuitBreaker("Foo"));

        // Since it hasn't been set, default value is now
        $this->assertEquals(new \DateTime(), $breaker->getLastTrippedAt());

        $breaker->trip();
        $trippedAt = new \DateTime();

        sleep(1);

        $this->assertEquals($trippedAt, $breaker->getLastTrippedAt());
    }

    public function testSetState()
    {
        $breaker = (new CircuitBreaker("Foo"))->setState(CircuitBreaker::HALF_OPEN);

        $this->assertEquals(CircuitBreaker::HALF_OPEN, $breaker->getState());
    }

    public function testSetInvalidState()
    {
        // We expect this to fail gracefully, no exceptions thrown
        $breaker = (new CircuitBreaker("Foo"))->setState("invalid");

        $this->assertEquals(CircuitBreaker::CLOSED, $breaker->getState());
    }

    public function testSetGetCache()
    {
        $cache = new MyCache(new Pool());
        $breaker = (new CircuitBreaker("Foo"))->setCache($cache);

        $this->assertTrue($breaker->getCache() instanceof MyCache);
    }

    public function testSetGetMonitor()
    {
        $breaker = (new CircuitBreaker("Foo"));

        $this->assertTrue($breaker->getMonitor() instanceof Monitor);
    }
}
