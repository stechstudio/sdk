<?php
namespace STS\Sdk\Service;

use Stash\DriverList;
use Stash\Interfaces\DriverInterface;
use Stash\Pool;
use STS\Sdk\CircuitBreaker;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * Class Description
 * @package RC\Sdk
 */
class Description
{
    /**
     * @var
     */
    protected $config;

    /**
     * @var Pool
     */
    protected $cachePool;

    /**
     * @var
     */
    protected $circuitBreaker;

    /**
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->verifyConfig();
    }

    /**
     * @param $descriptionFile
     *
     * @return static
     */
    public static function loadFromFile($descriptionFile)
    {
        if(!file_exists($descriptionFile)) {
            throw new FileNotFoundException("Description file not found");
        }

        return new static(include($descriptionFile));
    }

    /**
     * @param       $name
     * @param array $data
     *
     * @return Operation
     */
    public function getOperation($name, $data = [])
    {
        return array_key_exists($name, $this->config['operations'])
            ? new Operation($name, $this->config['operations'][$name], $data)
            : null;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->config['name'];
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        return $this->config['baseUrl'];
    }

    /**
     * @return array
     */
    public function getErrorHandlers()
    {
        return isset($this->config['errorHandlers']) && is_array($this->config['errorHandlers'])
            ? $this->config['errorHandlers']
            : [];
    }

    /**
     * @return bool
     */
    public function wantsCache()
    {
        return isset($this->config['cache']) && isset($this->config['cache']['driver']);
    }

    /**
     * @return Pool
     */
    public function getCachePool()
    {
        if(!$this->cachePool) {
            $this->initCachePool();
        }

        return $this->cachePool;
    }

    /**
     * @return Pool
     */
    protected function initCachePool()
    {
        return new Pool($this->getCacheDriver());
    }

    /**
     * @return DriverInterface
     */
    protected function getCacheDriver()
    {
        if(is_array($this->config['cache']['driver']) && isset($this->config['cache']['driver']['name']) && isset($this->config['cache']['driver']['options'])) {
            return $this->buildCacheDriverFromConfig($this->config['cache']['driver']);
        }

        return $this->buildCacheDriverFromBuilder($this->config['cache']['driver']);
    }

    /**
     * @param $config
     *
     * @return bool
     */
    protected function buildCacheDriverFromConfig($config)
    {
        $driver = DriverList::getDriverClass($config['name']);

        if(!$driver) {
            throw new \InvalidArgumentException("Invalid cache driver [" . $config['name'] . "]");
        }

        $driver->setOptions($config['options']);

        return $driver;
    }

    /**
     * @param $builder
     *
     * @return mixed
     */
    protected function buildCacheDriverFromBuilder($builder)
    {
        if(is_callable($builder)) {
            $driver = call_user_func($builder);
        }

        if(is_string($builder) && class_exists($builder, true)) {
            $instance = container()->make($builder);
            $driver = call_user_func($instance);
        }

        if(!isset($driver) || !$driver instanceof DriverInterface) {
            throw new \InvalidArgumentException("Cache driver builder invalid or returned an invalid driver");
        }

        return $driver;
    }

    /**
     * @return bool
     */
    public function wantsCircuitBreaker()
    {
        return isset($this->config['circuitBreaker']) && is_array($this->config['circuitBreaker']);
    }

    /**
     * @return CircuitBreaker
     */
    public function getCircuitBreaker()
    {
        if(!$this->circuitBreaker) {
            $this->initCircuitBreaker();
        }

        return $this->circuitBreaker;
    }

    /**
     *
     */
    protected function initCircuitBreaker()
    {
        $config = $this->config['circuitBreaker'];

        $this->circuitBreaker = (new CircuitBreaker())
            ->setName($this->getName())
            ->loadConfig($config)
            ->setCachePool($this->getCachePool());
    }

    /**
     * @return null
     */
    public function getCircuitBreakerConfig()
    {
        return $this->config['circuitBreaker'];
    }

    /**
     *
     */
    protected function verifyConfig()
    {
        foreach(['name','baseUrl','operations'] AS $key)
        {
            if(!array_key_exists($key, $this->config)) {
                throw new \InvalidArgumentException("Description must contain the top-level key '$key'");
            }
        }

        if(!is_array($this->config['operations'])) {
            throw new \InvalidArgumentException("List of 'operations' must be an array");
        }
    }

    /**
     * Give me a callable or a class path (that is invokable)
     * @param $input
     *
     * @return mixed
     */
    protected function getCallableValue($input)
    {
        if(is_callable($input)) {
            return call_user_func($input);
        }

        if(is_string($input) && class_exists($input, true)) {
            $instance = container()->make($input);
            return call_user_func($instance);
        }

        return false;
    }
}
