<?php
namespace STS\Sdk;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7;
use Stash\Pool;
use STS\Sdk\CircuitBreaker\BreakerPanel;
use STS\Sdk\CircuitBreaker\BreakerSwitch;
use STS\Sdk\Service\Description;
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
     * @var Pool
     */
    protected $cachePool;

    /**
     * @var BreakerSwitch
     */
    protected $breakerSwitch;


    /**
     * @param ClientInterface $client
     * @param                 $serviceName
     * @param Description     $description
     * @param Operation       $operation
     * @param                 $data
     * @param                 $cachePool
     * @param                 $breakerSwitch
     */
    public function __construct(ClientInterface $client, $serviceName, Description $description, Operation $operation, $data, $cachePool, $breakerSwitch)
    {
        $this->client = $client;
        $this->serviceName = $serviceName;
        $this->description = $description;
        $this->operation = $operation;
        $this->data = $data;

        $this->request = new GuzzleRequest($operation->getHttpMethod(), '');
        $this->cachePool = $cachePool;
        $this->breakerSwitch = $breakerSwitch;
    }

    /**
     * @return mixed
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * @return Description
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
     * @return bool
     */
    public function hasBreakerSwitch()
    {
        return $this->breakerSwitch instanceof BreakerSwitch;
    }

    /**
     * @return BreakerSwitch
     */
    public function getBreakerSwitch()
    {
        return $this->breakerSwitch;
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
