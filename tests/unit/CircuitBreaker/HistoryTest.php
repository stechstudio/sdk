<?php
namespace STS\Sdk\CircuitBreaker;

class HistoryTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaults()
    {
        $history = new History();

        $this->assertEquals(0, $history->failures());
        $this->assertEquals(0, $history->successes());
        $this->assertEquals(300, $history->getFailureInterval());
    }

    public function testEvents()
    {
        $history = new History();

        $history->add("failure");
        $history->add("failure");
        $history->add("success");

        $this->assertEquals(2, $history->failures());
        $this->assertEquals(1, $history->successes());

        $history->clear();
        $this->assertEquals(0, $history->failures());
        $this->assertEquals(0, $history->successes());
    }

    public function testFailureInterval()
    {
        $history = new History();
        $history->setFailureInterval(2);

        $history->add("failure");
        $history->add("failure");
        $history->add("failure");

        $this->assertEquals(3, $history->failures());

        sleep(1);

        $history->add("failure");
        $this->assertEquals(4, $history->failures());

        sleep(1);

        $this->assertEquals(1, $history->failures());
    }

    public function testSetGetEvents()
    {
        $events = ["foo" => "bar"];

        $history = new History();
        $history->set($events);

        $this->assertEquals($events, $history->toArray());
    }
}
