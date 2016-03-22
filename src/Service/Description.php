<?php
namespace STS\Sdk\Service;

use Stash\Interfaces\DriverInterface;
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
     * @return DriverInterface
     */
    public function getCacheDriver()
    {
       return $this->getCallableValue($this->config['cache']['driver']);
    }

    /**
     * @return bool
     */
    public function wantsCircuitBreaker()
    {
        return isset($this->config['circuitBreaker']) && is_array($this->config['circuitBreaker']);
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
        foreach(['baseUrl','operations'] AS $key)
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
    }
}
