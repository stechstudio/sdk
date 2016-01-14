<?php
namespace RC\Sdk;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Contracts\Container\Container;

/**
 * Class HttpClient
 * @package Sdk
 */
class HttpClient
{
    /**
     * @var
     */
    protected $container;

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

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getGuzzle(){
        return $this->getClient();
    }

    /**
     * @return array
     */
    public function getRequestMiddleware()
    {
        return $this->requestMiddleware;
    }

    /**
     * @return array
     */
    public function getResponseMiddleware()
    {
        return $this->responseMiddleware;
    }

    protected function ensureCallable($test){
        // If it is a string, and the string says it is callable
        // and the string is a valid class name, create the callable object and return it
        if (is_string($test) && is_callable($test, true, $callable_name) && class_exists($test)){
            return $this->container->make($test);
        }

        // Otherwise, just return it, it will throw an exception somewhere if it is bad.
        return $test;
    }

    /**
     * @param array $middleware
     */
    public function setRequestMiddleware($middleware = [])
    {
        foreach ($middleware as $key => $value){
            $middleware[$key] = $this->ensureCallable($value);
        }
        $this->requestMiddleware = $middleware;
    }

    /**
     * @param array $middleware
     */
    public function setResponseMiddleware($middleware = [])
    {
        foreach ($middleware as $key => $value){
            $middleware[$key] = $this->ensureCallable($value);
        }
        $this->responseMiddleware = $middleware;
    }

    /**
     * @param $middleware
     */
    public function addRequestMiddleware($middleware)
    {
        $this->requestMiddleware[] = $this->ensureCallable($middleware);
    }

    /**
     * @param $middleware
     */
    public function addResponseMiddleware($middleware)
    {
        $this->responseMiddleware[] = $this->ensureCallable($middleware);
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