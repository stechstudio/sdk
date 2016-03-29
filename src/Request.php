<?php
namespace STS\Sdk;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7;
use Stash\Pool;
use STS\Sdk\CircuitBreaker\BreakerPanel;
use STS\Sdk\CircuitBreaker\BreakerSwitch;
use STS\Sdk\Service;
use STS\Sdk\Service\Operation;

/**
 * This encapsulates a service request, basically a DTO. This is what gets sent through the pipeline.
 * @package RC\Sdk
 */
class Request
{

    // Initial service request

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var
     */
    protected $serviceName;

    /**
     * @var Service
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

    protected $options = [
        'timeout' => 10
    ];


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
     * @param ClientInterface $client
     * @param                 $serviceName
     * @param Service         $description
     * @param Operation       $operation
     * @param                 $data
     */
    public function __construct(ClientInterface $client, $serviceName, Service $description, Operation $operation, $data)
    {
        $this->client = $client;
        $this->serviceName = $serviceName;
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
     * @return Service
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return Operation
     */
    public function getOperation()
    {
        return $this->operation;
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
        return $this->client->send($this->request, $this->getRequestOptions());
    }

    public function getRequestOptions()
    {
        return array_merge($this->options, $this->getDescription()->getOptions(), $this->getOperation()->getOptions());
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
