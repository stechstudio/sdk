<?php
namespace RC\Sdk;

/**
 * This encapsulates a service request, basically a DTO. This is what gets sent through the pipeline.
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
     * @var
     */
    public $signingKey;

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
    public $parameters = [];

    /**
     * @var array
     */
    public $arguments;


    // Pieces of the HTTP request as they are being prepared

    /**
     * $var string
     */
    public $method;

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

    /**
     * @var string
     */
    public $signature = null;


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
     * @param            $signingKey
     * @param            $baseUrl
     * @param            $config
     * @param            $arguments
     */
    public function __construct(HttpClient $client, $signingKey, $baseUrl, $config, $arguments)
    {
        $this->client = $client;
        $this->signingKey = $signingKey;
        $this->baseUrl = $baseUrl;
        $this->config = $config;
        $this->arguments = $arguments;

        if(is_array($config['parameters'])) {
            $this->parameters = $config['parameters'];
        }

        if(!isset($config['httpMethod']) || !in_array(strtoupper($config['httpMethod']), ["GET","POST","PUT","PATCH","DELETE"])) {
            throw new \InvalidArgumentException("No httpMethod defined");
        }
        $this->method = strtoupper($config['httpMethod']);
    }

    /**
     * @param $name
     * @param $value
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * @param null $location
     *
     * @return array
     */
    public function getArguments($location = null)
    {
        if($location == null) {
            return $this->arguments;
        }

        // Ok we're looking for arguments with a specific location. First gram the parameters from
        // config that are assigned to this location.
        $parameters = array_keys(array_filter($this->parameters, function($details) use($location) {
            return $details['location'] == $location;
        }));

        // Now return arguments that have the same key
        return array_intersect_key($this->arguments, array_flip($parameters));
    }
}