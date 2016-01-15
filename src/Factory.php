<?php
namespace RC\Sdk;

/**
 * Class Factory
 * @package RC\Sdk
 */
class Factory
{
    /**
     * @param      $name
     * @param null $key
     *
     * @return mixed
     */
    public static function create($name, $key = null)
    {
        $service = self::loadService($name);
        $service->setName($name);

        if($key != null) {
            $service->setKey($key);
        }

        return $service;
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public static function loadService($name)
    {
        $serviceClass = 'RC\Sdk\\' . $name . '\Service';
        if (!class_exists($serviceClass, true)) {
            $serviceClass = Service::class;
        }

        return $serviceClass::create();
    }
}