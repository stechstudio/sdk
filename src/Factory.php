<?php
namespace RC\Sdk;

/**
 * Class Factory
 * @package RC\Sdk
 */
class Factory
{
    /**
     * Need a built-in service? Use this, just specify the name (e.g. "Curator")
     *
     * @param string $name
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
     * Have your own description array? Use this method.
     *
     * @param array $description
     * @param null $key
     *
     * @return AbstractServiceClient
     */
    public static function createWithDescription($description, $key = null)
    {
        $service = ServiceClient::create($key);
        $service->setDescription($description);

        return $service;
    }

    /**
     * Loads a service by first looking for a dedicated service class, and
     * falls back to our generic service class.
     *
     * @param $name
     *
     * @return mixed
     */
    public static function loadService($name)
    {
        $serviceClass = 'RC\Sdk\\' . $name . '\Service';
        if (!class_exists($serviceClass, true)) {
            $serviceClass = ServiceClient::class;
        }

        return $serviceClass::create();
    }
}