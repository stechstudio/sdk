<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 1/16/16
 * Time: 3:09 PM
 */

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
     * @param       $name
     * @param       $config
     * @param array $data
     */
    public function __construct($name, $config, $data = [])
    {
        $this->name = $name;
        $this->data = $data;

        $configDefaults = [
            'httpMethod' => '',
            'uri' => '',
            'notes' => '',
            'summary' => '',
            'documentationUrl' => null,
            'deprecated' => false,
            'parameters' => [],
            'additionalParameters' => null,
        ];

        $this->config = array_merge($configDefaults, $config);

        $this->resolveParameters();
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

            $this->parameters[$name] = new Parameter($name, $config);
        }

        if ($this->config['additionalParameters'] && is_array($this->config['additionalParameters'])) {
            $this->additionalParameters = new Parameter('*', $this->config['additionalParameters']);
        }
    }

    /**
     * Cuz I'm too lazy to write getters??
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if(strpos($name, "get") === 0) {
            // This is a getter
            $parameterName = strtolower($name[3]) . substr($name, 4);
            return array_key_exists($parameterName, $this->config)
                ? $this->config[$parameterName]
                : null;
        }
    }
}