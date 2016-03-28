<?php
namespace STS\Sdk\Service;

    /**
     * Class Operation
     * @package RC\Sdk
     */
    /**
     * Class Operation
     * @package RC\Sdk\Config
     */
/**
 * Class Operation
 * @package STS\Sdk\Service
 */
/**
 * Class Operation
 * @package STS\Sdk\Service
 */
/**
 * Class Operation
 * @package STS\Sdk\Service
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
        $rules = [];

        foreach ($this->getParameters() AS $parameter) {
            $rules[$parameter->getName()] = $parameter->getValidate();
        }

        return array_filter($rules);
    }

    /**
     * @param $name
     *
     * @return null
     */
    public function getParameter($name)
    {
        return array_get($this->parameters, $name);
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
     * @param $location
     *
     * @return bool
     */
    public function allowAdditionalParametersAt($location)
    {
        return $this->additionalParameters instanceof Parameter
        && $this->additionalParameters->getLocation() == $location;
    }

    /**
     * Return the full data, including defaults. Note we have to getName() here because the parameter may have an
     * alternate key (`sentAs`)
     */
    public function getData()
    {
        $return = [];

        // First get the data that belongs with out mapped parameters
        foreach ($this->getParameters() AS $parameter) {
            $return[$parameter->getName()] = $parameter->getValue();
        }

        // Do we allow additional parameters? If so, add the rest of the data
        if ($this->additionalParameters != null) {
            $return = $return + array_diff_key($this->data, $this->getParameters());
        }

        return array_filter($return, 'is_not_null');
    }

    /**
     * Note we have to getName() here because the parameter may have an alternate key (`sentAs`)
     *
     * @param $location
     *
     * @return array
     */
    public function getDataByLocation($location)
    {
        $return = [];

        // First get the data that matches parameters at this location
        foreach ($this->getParametersByLocation($location) AS $parameter) {
            $return[$parameter->getName()] = $parameter->getValue();
        }

        // Now add the additional data only if we allow at this location
        if ($this->additionalParameters != null && $this->additionalParameters->getLocation() == $location) {
            $return = $return + array_diff_key($this->data, $this->getParameters());
        }

        return $return;
    }

    /**
     * @return mixed
     */
    public function wantsCache()
    {
        return (bool)array_get($this->config, "cache.fallback", $this->getHttpMethod() == "GET");
    }

    /**
     * @return bool
     */
    public function prefersCache()
    {
        return (bool)array_get($this->config, "cache.prefers", false);
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

        if (is_array(array_get($this->config, 'additionalParameters'))) {
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

    /**
     * @return array
     */
    public function getOptions()
    {
        return (array)array_get($this->config, "options");
    }
}
