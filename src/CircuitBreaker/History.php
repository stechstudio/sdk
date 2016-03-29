<?php
namespace STS\Sdk\CircuitBreaker;

use DateTime;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Class History
 * @package STS\Sdk\CircuitBreaker
 */
class History implements Arrayable
{
    /**
     * @var array
     */
    protected $events = [];

    /**
     * @var int
     */
    protected $failureInterval = 300;

    /**
     * @param array $events
     */
    public function set(array $events)
    {
        $this->events = $events;
    }

    /**
     * @param $event
     */
    public function add($event)
    {
        if (!array_key_exists($event, $this->events)) {
            $this->events[$event] = [];
        }

        array_push($this->events[$event], new DateTime());
    }

    /**
     * Get rid of all history events
     */
    public function clear()
    {
        $this->events = [];
    }

    /**
     * @return int
     */
    public function failures()
    {
        $this->clean("failure", $this->failureInterval);

        return array_key_exists('failure', $this->events)
            ? count($this->events['failure'])
            : 0;
    }

    /**
     * @return int
     */
    public function successes()
    {
        return array_key_exists('success', $this->events)
            ? count($this->events['success'])
            : 0;
    }

    /**
     * @param $event
     * @param $interval
     */
    public function clean($event, $interval)
    {
        if(!array_key_exists($event, $this->events)) {
            return;
        }

        // remove any array items that are outside the interval
        $this->events[$event] = array_filter($this->events[$event], function($eventDateTime) use($interval) {
            return (time() - $eventDateTime->getTimestamp()) < $interval;
        });
    }

    /**
     * @return int
     */
    public function getFailureInterval()
    {
        return $this->failureInterval;
    }

    /**
     * @param int $failureInterval
     */
    public function setFailureInterval($failureInterval)
    {
        $this->failureInterval = $failureInterval;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $this->clean("failure", $this->failureInterval);

        return $this->events;
    }
}
