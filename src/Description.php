<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 1/16/16
 * Time: 3:14 PM
 */

namespace RC\Sdk;


/**
 * Class Description
 * @package RC\Sdk
 */
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

    public function getBaseUrl()
    {
        return $this->config['baseUrl'];
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
}