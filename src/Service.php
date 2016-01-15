<?php
namespace RC\Sdk;

/**
 * Class Service
 * @package RC\Sdk
 */
class Service extends AbstractService
{
    /**
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}