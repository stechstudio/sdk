<?php
namespace STS\Sdk\CircuitBreaker;

use STS\Sdk\CircuitBreaker;
use Psr\Log\LoggerInterface;

class MonitorTest extends \PHPUnit_Framework_TestCase
{
    public function testCallbacks()
    {
        $monitor = new Monitor();
        $breaker = make(CircuitBreaker::class)->setName("Foo");
        $counter = 0;
        $result = "";

        // First one just increments counter
        $monitor->on("foo", function($event, $breaker, $context) use(&$counter) {
            $counter++;
        });

        // Now throw an exception to prove we're here with the right details
        $monitor->on("foo", function($event, $breaker, $context) use(&$counter, &$result) {
            $result = "Got [$event] event for [{$breaker->getName()}] breaker. Counter is $counter.";
        });

        $monitor->handle("foo", $breaker);

        $this->assertEquals("Got [foo] event for [Foo] breaker. Counter is 1.", $result);
    }

    public function testNoCallbacks()
    {
        $monitor = new Monitor();
        $breaker = make(CircuitBreaker::class)->setName("Foo");

        // Nothing should happen, no exceptions, no return
        $this->assertNull($monitor->handle("foo", $breaker));
    }

    public function testLogger()
    {
        $GLOBALS['loglevel'] = '';
        $monitor = new Monitor();
        $breaker = make(CircuitBreaker::class)->setName("Foo");

        $monitor->setLogger(new MonitorTestLogger());

        $this->assertTrue($monitor->getLogger() instanceof MonitorTestLogger);

        $monitor->handle("foo", $breaker);
        $this->assertEquals("debug", $GLOBALS['loglevel']);

        $monitor->handle("trip", $breaker);
        $this->assertEquals("critical", $GLOBALS['loglevel']);

        $monitor->handle("failure", $breaker);
        $this->assertEquals("error", $GLOBALS['loglevel']);

        $monitor->handle("reset", $breaker);
        $this->assertEquals("info", $GLOBALS['loglevel']);
    }

    public function testEventHandlerClass()
    {
        $monitor = new Monitor();
        $breaker = make(CircuitBreaker::class)->setName("Foo");

        $monitor->on("foo", MonitorEventHandler::class);

        $this->setExpectedException(\Exception::class, "Got event [foo] for breaker [Foo]");
        $monitor->handle("foo", $breaker);
    }

    public function testInvalidHandler()
    {
        $monitor = new Monitor();

        $this->setExpectedException(\InvalidArgumentException::class);

        $monitor->on("foo", "invalid");
    }
}


class MonitorEventHandler {
    public function __invoke($event, $breaker, $context)
    {
        throw new \Exception("Got event [$event] for breaker [{$breaker->getName()}]");
    }
}

class MonitorTestLogger implements LoggerInterface {
    public function emergency($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'emergency';
    }
    public function alert($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'alert';
    }
    public function critical($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'critical';
    }
    public function error($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'error';
    }
    public function warning($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'warning';
    }
    public function notice($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'notice';
    }
    public function info($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'info';
    }
    public function debug($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'debug';
    }
    public function log($level, $message, array $context = array()) {
        return $this->{$level}($message, $context);
    }
}
