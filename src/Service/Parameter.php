<?php
namespace STS\Sdk\Service;

/**
 * Class Parameter
 * @package RC\Sdk
 */
class Parameter
{
    /**
     * @var
     */
    protected $name;

    /**
     * @var
     */
    protected $value;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param $name
     * @param $value
     * @param $config
     */
    public function __construct($name, $value, $config)
    {
        $this->name = $name;
        $this->value = $value;

        $configDefaults = [
            'validate' => null,
            'location' => null,
            'default' => null,
            'sentAs' => null
        ];

        $this->config = array_merge($configDefaults, $config);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return ($this->value == null)
            ? $this->config['default']
            : $this->value;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return ($this->config['sentAs'] == null)
            ? $this->name
            : $this->config['sentAs'];
    }

    /**
     * @return mixed
     */
    public function getValidate()
    {
        return $this->config['validate'];
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->config['default'];
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->config['location'];
    }
}