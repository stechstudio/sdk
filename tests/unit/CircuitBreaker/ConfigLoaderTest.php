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

    public function testLogger()
    {
        /** @var CircuitBreaker $breaker */
        $breaker = make(CircuitBreaker::class)->setName("Foo");

        (new ConfigLoader())->load($breaker, [
            'logger' => MyLogger::class
        ]);

        $this->assertTrue($breaker->getMonitor()->getLogger() instanceof MyLogger);
    }
}

class EventHandler {
    public function __invoke($event, $breaker, $context)
    {
        throw new \Exception("Got event [$event] for breaker [{$breaker->getName()}]");
    }
}

class MyLogger implements LoggerInterface {
    public function emergency($message, array $context = array()) {
        throw new \Exception('emergency');
    }
    public function alert($message, array $context = array()) {
        throw new \Exception('alert');
    }
    public function critical($message, array $context = array()) {
        throw new \Exception('critical');
    }
    public function error($message, array $context = array()) {
        throw new \Exception('error');
    }
    public function warning($message, array $context = array()) {
        throw new \Exception('warning');
    }
    public function notice($message, array $context = array()) {
        throw new \Exception('notice');
    }
    public function info($message, array $context = array()) {
        throw new \Exception('info');
    }
    public function debug($message, array $context = array()) {
        throw new \Exception('debug');
    }
    public function log($level, $message, array $context = array()) {
        return $this->{$level}($message, $context);
    }
}
