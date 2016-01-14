<?php
namespace RC\Sdk;

/**
 * This encapsulates a service request, basically a DTO. This is what gets sent through the pipeline.
 * @package RC\Sdk
 */
/**
 * Class Request
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
    public $arguments;

    /**
     * @var null
     */
    public $body = null;

    /**
     * @var null
     */
    public $headers = [];

    /**
     * @var null
     */
    public $url = null;

    /**
     * @var
     */
    public $response = null;

    /**
     * @var null
     */
    public $responseBody = null;

    /**
     * Request constructor.
     *
     * @param $baseUrl
     * @param $config
     * @param $arguments
     */
    public function __construct($client, $baseUrl, $config, $arguments)
    {
        $this->client = $client;
        $this->baseUrl = $baseUrl;
        $this->config = $config;
        $this->arguments = $arguments;
    }
}