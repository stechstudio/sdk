<?php
namespace Sdk;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

/**
 * Class HttpClient
 * @package Sdk
 */
class HttpClient
{
    /**
     * @var null
     */
    protected $guzzle = null;
    /**
     * @var array
     */
    protected $requestMiddleware = [];
    /**
     * @var array
     */
    protected $responseMiddleware = [];

    /**
     * @param array $middleware
     */
    public function setRequestMiddleware($middleware = [])
    {
        $this->requestMiddleware = $middleware;
    }

    /**
     * @param array $middleware
     */
    public function setResponseMiddleware($middleware = [])
    {
        $this->responseMiddleware = $middleware;
    }

    /**
     * @param $middleware
     */
    public function addRequestMiddleware($middleware)
    {
        $this->requestMiddleware[] = $middleware;
    }

    /**
     * @param $middleware
     */
    public function addResponseMiddleware($middleware)
    {
        $this->responseMiddleware[] = $middleware;
    }

    /**
     * @return GuzzleClient|null
     */
    protected function getClient()
    {
        if($this->guzzle == null) {
            $this->guzzle = $this->buildClient();
        }

        return $this->guzzle;
    }

    /**
     * @return GuzzleClient
     */
    protected function buildClient()
    {
        return new GuzzleClient(['handler' => $this->buildStack()]);
    }

    /**
     * @return HandlerStack
     */
    protected function buildStack()
    {
        // The static `create` sets up the default stack for us
        $stack = HandlerStack::create();

        // Map our request middleware
        foreach($this->requestMiddleware AS $middleware) {
            $stack->push(Middleware::mapRequest($middleware));
        }

        // Map our response middleware
        foreach($this->responseMiddleware AS $middleware) {
            $stack->push(Middleware::mapResponse($middleware));
        }

        return $stack;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->getClient(), $name], $arguments);
    }
}