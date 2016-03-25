<?php
namespace STS\Sdk\CircuitBreaker;

use STS\Sdk\CircuitBreaker;
use Psr\Log\LoggerInterface;

/**
 * Class Monitor
 * @package STS\Sdk\CircuitBreaker
 */
class Monitor
{
    /**
     * @var array
     */
    protected $callbacks = [];

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param string $event
     * @param string|callable $callback
     */
    public function on($event, $callback)
    {
        if(!array_key_exists($event, $this->callbacks)) {
            $this->callbacks[$event] = [];
        }

        $this->callbacks[$event][] = $this->getCallable($callback);
    }

    /**
     * @param                $event
     * @param CircuitBreaker $breaker
     * @param null           $context
     */
    public function handle($event, CircuitBreaker $breaker, $context = null)
    {
        $this->log($event, $breaker, $context);

        if (!array_key_exists($event, $this->callbacks) || !is_array($this->callbacks[$event])) {
            return;
        }

        foreach($this->callbacks[$event] AS $callback) {
            call_user_func($callback, $event, $breaker, $context);
        }
    }

    /**
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param                $event
     * @param CircuitBreaker $breaker
     * @param                $context
     */
    protected function log($event, CircuitBreaker $breaker, $context)
    {
        if(!$this->getLogger()) {
            return;
        }

        $this->getLogger()->log($this->getLogLevel($event), "[" . ucwords($event) . "] circuit breaker event", [
            'name' => $breaker->getName(),
            'event' => $event,
            'context' => $context
        ]);
    }

    /**
     * @param $event
     *
     * @return string
     */
    protected function getLogLevel($event)
    {
        switch($event) {
            case 'trip':
                return 'critical';
            case 'failure':
                return 'error';
            case 'reset':
                return 'info';
            default:
                return 'debug';
        }
    }

    /**
     * By using this wrapper, you can pass in a Class path string as a callback
     * provided the class has an __invoke method.
     *
     * @param $item
     *
     * @return mixed
     */
    protected function getCallable($item)
    {
        if (is_string($item) && class_exists($item, true)) {
            $item = make($item);
        }

        if (is_callable($item)) {
            return $item;
        }

        throw new \InvalidArgumentException("Not a valid callable [$item]");
    }
}
