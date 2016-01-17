<?php
namespace RC\Sdk;

/**
 * Class Operation
 * @package RC\Sdk
 */
/**
 * Class Operation
 * @package RC\Sdk
 */
class Operation
{
    /**
     * @var
     */
    protected $name;

    /**
     * @var
     */
    protected $config;

    /**
     * @var
     */
    protected $data;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var Parameter|null
     */
    protected $additionalParameters = null;

    /**
     * @param string $name
     * @param array  $config
     * @param array  $data
     */
    public function __construct($name, $config, $data = [])
    {
        $this->name = $name;
        $this->data = $data;

        $configDefaults = [
            'httpMethod' => '',
            'uri' => '',
            'parameters' => [],
            'additionalParameters' => null,
        ];

        $this->config = array_merge($configDefaults, $config);

        $this->resolveParameters();
    }

    /**
     * @return array
     */
    public function getValidationRules()
    {
        return array_map(function ($parameter) {
            return $parameter->getValidate();
        }, $this->getParameters());
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param $location
     *
     * @return array
     */
    public function getParametersByLocation($location)
    {
        return array_filter($this->parameters, function ($parameter) use ($location) {
            return $parameter->getLocation() == $location;
        });
    }

    /**
     * Return the full data, including defaults
     */
    public function getData()
    {
        return array_map(function ($parameter) {
            return $parameter->getValue();
        }, $this->getParameters());
    }

    /**
     * @param $location
     *
     * @return array
     */
    public function getDataByLocation($location)
    {
        return array_map(function ($parameter) {
            return $parameter->getValue();
        }, $this->getParametersByLocation($location));
    }

    /**
     * Setup each parameter config with an instance of Parameter
     */
    protected function resolveParameters()
    {
        // Parameters need special handling when adding
        foreach ($this->config['parameters'] as $name => $config) {
            if (!is_array($config)) {
                throw new \InvalidArgumentException('Parameters must be arrays');
            }

            $value = (isset($this->data[$name]))
                ? $this->data[$name]
                : null;

            $this->parameters[$name] = new Parameter($name, $value, $config);
        }

        if ($this->config['additionalParameters'] && is_array($this->config['additionalParameters'])) {
            $this->additionalParameters = new Parameter('*', null, $this->config['additionalParameters']);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getHttpMethod()
    {
        return $this->config['httpMethod'];
    }

    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->config['uri'];
    }
}