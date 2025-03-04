<?php
namespace STS\Sdk;

use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use Stash\DriverList;
use Stash\Interfaces\DriverInterface;
use Stash\Pool;
use STS\Sdk\Service\CircuitBreaker;
use STS\Sdk\CircuitBreaker\Cache;
use STS\Sdk\Service\Operation;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

/**
 * Class Service
 * @package RC\Sdk
 */
class Service
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
        if (!file_exists($descriptionFile)) {
            throw new FileNotFoundException("Service description file not found");
        }

        return new static(include($descriptionFile));
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function hasOperation($name)
    {
        return Arr::has($this->config['operations'], $name);
    }

    /**
     * @param       $name
     * @param array $data
     *
     * @return Operation
     */
    public function getOperation($name, $data = [])
    {
        return Arr::has($this->config['operations'], $name)
            ? new Operation($name, $this->config['operations'][$name], $data)
            : null;
    }

    /**
     * @param Operation $operation
     */
    public function addOperation(Operation $operation)
    {
        $this->config['operations'][$operation->getName()] = $operation;
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
        return (array)Arr::get($this->config, 'errorHandlers');
    }

    /**
     * @return bool
     */
    public function hasLogger()
    {
        return isset($this->config['logger']);
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        $logger = build($this->config['logger']);

        if(!$logger instanceof LoggerInterface) {
            throw new \InvalidArgumentException("Invalid logger provided");
        }

        return $logger;
    }

    /**
     * @return bool
     */
    public function wantsCache()
    {
        return Arr::has($this->config, 'cache.driver');
    }

    /**
     * @return Pool
     */
    public function getCachePool()
    {
        if (!$this->cachePool) {
            $this->cachePool = $this->buildCachePool();
        }

        return $this->cachePool;
    }

    /**
     * @return Pool
     */
    protected function buildCachePool()
    {
        $pool = new Pool($this->buildCacheDriver());
        $pool->setNamespace(Arr::get($this->config, "cache.namespace"));

        return $pool;
    }

    /**
     * @return DriverInterface
     */
    protected function buildCacheDriver()
    {
        if (!$this->wantsCache()) {
            throw new \InvalidArgumentException("No valid cache driver config found");
        }

        $driverClass = DriverList::getDriverClass(Arr::get($this->config, "cache.driver.name"));

        if (!$driverClass) {
            throw new \InvalidArgumentException("Invalid cache driver [" . Arr::get($this->config, "cache.driver.name") . "]");
        }

        $driver = new $driverClass((array)Arr::get($this->config, "cache.driver.options"));

        return $driver;
    }

    /**
     * @return bool
     */
    public function wantsCircuitBreaker()
    {
        return $this->wantsCache() && is_array(Arr::get($this->config, 'circuitBreaker'));
    }

    /**
     * @return CircuitBreaker
     */
    public function getCircuitBreaker()
    {
        if (!$this->circuitBreaker) {
            $this->circuitBreaker = $this->buildCircuitBreaker();
        }

        return $this->circuitBreaker;
    }

    /**
     * Builds a new CircuitBreaker and sets it up with our config
     */
    protected function buildCircuitBreaker()
    {
        if(!is_array(Arr::get($this->config, 'circuitBreaker'))) {
            throw new \InvalidArgumentException("No valid circuit breaker config found");
        }

        $breaker = new CircuitBreaker($this->getName(), $this->config['circuitBreaker']);
        $breaker->setCache(new Cache($this->getCachePool()));

        if($this->hasLogger()) {
            $breaker->getMonitor()->setLogger($this->getLogger());
        }

        return $breaker;
    }

    /**
     * @return array
     */
    public function getPrependedPipes()
    {
        return (array)Arr::get($this->config, "pipeline.prepend");
    }

    /**
     * @return array
     */
    public function getAppendedPipes()
    {
        return (array)Arr::get($this->config, "pipeline.append");
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return (array)Arr::get($this->config, "options");
    }

    /**
     *
     */
    protected function verifyConfig()
    {
        foreach (['name', 'baseUrl', 'operations'] AS $key) {
            if (!array_key_exists($key, $this->config)) {
                throw new \InvalidArgumentException("Description must contain the top-level key '$key'");
            }
        }

        if (!is_array($this->config['operations'])) {
            throw new \InvalidArgumentException("List of 'operations' must be an array");
        }
    }
}
