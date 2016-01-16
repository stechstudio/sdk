<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 1/16/16
 * Time: 3:06 PM
 */

namespace RC\Sdk;


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
     * @var array
     */
    protected $config = [];

    /**
     * @param $name
     * @param $config
     */
    public function __construct($name, $config)
    {
        $this->name = $name;

        $configDefaults = [
            'validate' => null,
            'location' => null,
            'default' => null
        ];

        $this->config = array_merge($configDefaults, $config);
    }


}