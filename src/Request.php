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

    // Initial service request

    /**
     * @var HttpClient
     */
    public $client;
    /**
     * @var string
     */
    public $baseUrl;
    /**
     * @var array
     */
    public $config;
    /**
     * @var array
     */
    public $arguments;


    // Pieces of the HTTP request as they are being prepared

    /**
     * @var string|null
     */
    public $body = null;

    /**
     * @var array
     */
    public $headers = [];

    /**
     * @var string
     */
    public $url = null;


    // Response from the HTTP request

    /**
     * @var
     */
    public $response = null;

    /**
     * @var array|string
     */
    public $responseBody = null;

    /**
     * Request constructor.
     *
     * @param HttpClient $client
     * @param            $baseUrl
     * @param            $config
     * @param            $arguments
     */
    public function __construct(HttpClient $client, $baseUrl, $config, $arguments)
    {
        $this->client = $client;
        $this->baseUrl = $baseUrl;
        $this->config = $config;
        $this->arguments = $arguments;
    }
}