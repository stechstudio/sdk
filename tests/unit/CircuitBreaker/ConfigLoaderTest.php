<?php
namespace STS\Sdk\CircuitBreaker;

use STS\Sdk\CircuitBreaker;
use Psr\Log\LoggerInterface;

class ConfigLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadAttributes()
    {
        /** @var CircuitBreaker $breaker */
        $breaker = make(CircuitBreaker::class);

        (new ConfigLoader())->load($breaker, [
            'failureThreshold' => 5,
            'failureInterval' => 100,
            'successThreshold' => 10,
            'autoRetryInterval' => 20,
            'foo' => 'bar' // Invalid parameters should just be ignored
        ]);

        $this->assertEquals(5, $breaker->getFailureThreshold());
        $this->assertEquals(100, $breaker->getFailureInterval());
        $this->assertEquals(10, $breaker->getSuccessThreshold());
        $this->assertEquals(20, $breaker->getAutoRetryInterval());
    }

    public function testLoadCallbacks()
    {
        /** @var CircuitBreaker $breaker */
        $breaker = make(CircuitBreaker::class);
        $counter = 0;

        (new ConfigLoader())->load($breaker, [
            'handlers' => [
                "success" => function($event, $breaker) use(&$counter) { $counter++; },
                "failure" => function($event, $breaker) use(&$counter) { $counter++; },
                "trip" => function($event, $breaker) use(&$counter) { $counter++; },
                "reset" => function($event, $breaker) use(&$counter) { $counter++; },
            ]
        ]);

        $breaker->success();
        $breaker->failure();
        $breaker->trip();
        $breaker->reset();

        $this->assertEquals($counter, 4);
    }

    public function testInvalidHandler()
    {
        /** @var CircuitBreaker $breaker */
        $breaker = make(CircuitBreaker::class);

        $this->setExpectedException(\InvalidArgumentException::class);

        (new ConfigLoader())->load($breaker, [
            'handlers' => [
                "success" => "invalid"
            ]
        ]);
    }

    public function testClassInvokeHandler()
    {
        /** @var CircuitBreaker $breaker */
        $breaker = make(CircuitBreaker::class)->setName("Foo");

        (new ConfigLoader())->load($breaker, [
            'handlers' => [
                "failure" => EventHandler::class
            ]
        ]);


        $this->setExpectedException(\Exception::class, "Got event [failure] for breaker [Foo]");
        $breaker->failure();
    }
}

class EventHandler {
    public function __invoke($event, $breaker, $context)
    {
        throw new \Exception("Got event [$event] for breaker [{$breaker->getName()}]");
    }
}