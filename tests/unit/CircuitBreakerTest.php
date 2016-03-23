<?php
namespace STS\Sdk;

use Stash\Driver\Ephemeral;
use Stash\Pool;

class CircuitBreakerTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiate()
    {
        $breaker = new CircuitBreaker("foo");

        $this->assertTrue($breaker instanceof CircuitBreaker);
        $this->assertEquals("foo", $breaker->getName());
    }

    public function testLoadFromCache()
    {
        $cache = new Pool(new Ephemeral());

        $breaker = (new CircuitBreaker("foo"))->setCachePool($cache);


    }
}
