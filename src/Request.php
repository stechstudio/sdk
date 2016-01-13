<?php
namespace RC\Sdk;

/**
 * This encapsulates a service request, basically a DTO. This is what gets sent through the pipeline.
 * @package RC\Sdk
 */
class Request
{
    /**
     * @var
     */
    public $client;
    /**
     * @var
     */
    public $baseUrl;
    /**
     * @var
     */
    public $config;
    /**
     * @var
     */
    public $parameters;

    /**
     * @var
     */
    public $response = null;

    /**
     * Request constructor.
     *
     * @param $baseUrl
     * @param $config
     * @param $parameters
     */
    public function __construct($client, $baseUrl, $config, $parameters)
    {
        $this->baseUrl = $baseUrl;
        $this->config = $config;
        $this->parameters = $parameters;
    }
}