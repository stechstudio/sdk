<?php
namespace STS\Sdk\CircuitBreaker;

use STS\Sdk\CircuitBreaker;

/**
 * Class ConfigLoader
 * @package STS\Sdk\CircuitBreaker
 */
class ConfigLoader
{
    /**
     * @var CircuitBreaker
     */
    protected $breaker;

    /**
     * @param CircuitBreaker $breaker
     * @param array          $config
     *
     * @return CircuitBreaker
     */
    public function load(CircuitBreaker $breaker, array $config)
    {
        return $this
            ->setBreaker($breaker)
            ->loadAttributes($config)
            ->registerCallbacks($config)
            ->setLogger($config)
            ->getBreaker();
    }

    /**
     * @return CircuitBreaker
     */
    public function getBreaker()
    {
        return $this->breaker;
    }

    /**
     * @param CircuitBreaker $breaker
     *
     * @return $this
     */
    public function setBreaker($breaker)
    {
        $this->breaker = $breaker;

        return $this;
    }

    /**
     * @param array $config
     *
     * @return $this
     */
    public function loadAttributes(array $config)
    {
        foreach (['failureThreshold', 'failureInterval', 'successThreshold', 'autoRetryInterval'] AS $attribute) {
            if (isset($config[$attribute])) {
                $setter = "set" . ucwords($attribute);
                $this->getBreaker()->{$setter}($config[$attribute]);
            }
        }

        return $this;
    }

    /**
     * @param array $config
     *
     * @return $this
     */
    public function registerCallbacks(array $config)
    {
        if (isset($config['handlers']) && is_array($config['handlers'])) {
            foreach ($config['handlers'] AS $event => $handler) {
                $this->getBreaker()->registerCallback($event, $handler);
            }
        }

        return $this;
    }

    /**
     * @param array $config
     *
     * @return $this
     */
    public function setLogger(array $config)
    {
        if(isset($config['logger'])) {
            $this->getBreaker()->getMonitor()->setLogger(make($config['logger']));
        }

        return $this;
    }
}
