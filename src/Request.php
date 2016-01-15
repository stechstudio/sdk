<?php
namespace RC\Sdk;

use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7;

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
    public $serviceName;

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


    // Guzzle HTTP request as it is being prepared

    /**
     * @var GuzzleRequest
     */
    public $request;


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
     * @param            $serviceName
     * @param            $signingKey
     * @param            $baseUrl
     * @param            $config
     * @param            $arguments
     */
    public function __construct(HttpClient $client, $serviceName, $signingKey, $baseUrl, $config, $arguments)
    {
        $this->client = $client;
        $this->signingKey = $signingKey;
        $this->baseUrl = $baseUrl;
        $this->config = $config;
        $this->arguments = $arguments;
        $this->serviceName = $serviceName;

        if (is_array($config['parameters'])) {
            $this->parameters = $config['parameters'];
        }

        if (!isset($config['httpMethod']) || !in_array(strtoupper($config['httpMethod']), ["GET", "POST", "PUT", "PATCH", "DELETE"])) {
            throw new \InvalidArgumentException("No httpMethod defined");
        }

        $this->request = new GuzzleRequest($config['httpMethod'], $this->baseUrl);
    }

    /**
     * @return mixed
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * @param $name
     * @param $value
     */
    public function setHeader($name, $value)
    {
        $this->request = $this->request->withHeader($name, $value);
    }

    /**
     * @param $contents
     */
    public function setBody($contents)
    {
        $this->request = $this->request->withBody(Psr7\stream_for($contents));
    }

    /**
     * @param $uri
     */
    public function setUri($uri)
    {
        $this->request = $this->request->withUri(new Uri($uri));
    }

    /**
     * @param null $location
     *
     * @return array
     */
    public function getArguments($location = null)
    {
        if ($location == null) {
            return $this->arguments;
        }

        // Return arguments that have the same key as the parameters for this location
        return array_intersect_key($this->arguments, array_flip($this->getParametersByLocation($location)));
    }

    /**
     * @param $location
     *
     * @return array
     */
    public function getParametersByLocation($location)
    {
        return array_keys(array_filter($this->parameters, function ($details) use ($location) {
            return $details['location'] == $location;
        }));
    }

    /**
     * The validation rules are currently in a sub-array for each parameter, need to flatten
     * this down to a simple $parameter -> $validationArray key/value pair.
     *
     * @return array
     */
    public function getValidationRules()
    {
        return array_map(function ($details) {
            if (isset($details['validate'])) {
                return $details['validate'];
            }

            return '';
        }, $this->parameters);
    }

    /**
     * Send our request, return the response
     *
     * @return mixed
     */
    public function send()
    {
        return $this->client->send($this->request);
    }

    /**
     * @return null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return array|string
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * Anything we don't provide, pass through to the Guzzle Request object
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->request, $name], $arguments);
    }
}