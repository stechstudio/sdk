<?php
namespace RC\Sdk;

use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7;
use RC\Sdk\Config\Description;
use RC\Sdk\Config\Operation;

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
    protected $client;

    /**
     * @var
     */
    protected $serviceName;

    /**
     * @var
     */
    protected $signingKey;

    /**
     * @var Description
     */
    protected $description;
    /**
     * @var Operation
     */
    protected $operation;
    /**
     * @var array
     */
    protected $data;


    // Guzzle HTTP request as it is being prepared

    /**
     * @var GuzzleRequest
     */
    protected $request;


    // Response from the HTTP request

    /**
     * @var
     */
    protected $response = null;

    /**
     * @var array|string
     */
    protected $responseBody = null;


    /**
     * @param HttpClient  $client
     * @param             $serviceName
     * @param             $signingKey
     * @param Description $description
     * @param Operation   $operation
     * @param             $data
     */
    public function __construct(HttpClient $client, $serviceName, $signingKey, Description $description, Operation $operation, $data)
    {
        $this->client = $client;
        $this->serviceName = $serviceName;
        $this->signingKey = $signingKey;
        $this->description = $description;
        $this->operation = $operation;
        $this->data = $data;

        $this->request = new GuzzleRequest($operation->getHttpMethod(), '');
    }

    /**
     * @return mixed
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * @return mixed
     */
    public function getSigningKey()
    {
        return $this->signingKey;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->getOperation()->getParameters();
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
     * Send our request, return the response
     *
     * @return mixed
     */
    public function send()
    {
        return $this->client->send($this->request);
    }

    /**
     * @param $response
     * @param $body
     */
    public function saveResponse($response, $body)
    {
        $this->response = $response;
        $this->responseBody = $body;
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