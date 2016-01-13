<?php
namespace Sdk;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Middleware;
use Sdk\Middleware\CorrelationID;

/**
 * Class AbstractClient
 * @package Sdk
 */
class AbstractClient
{
    /**
     * @var GuzzleClient
     */
    protected $client;

    /**
     * @var array
     */
    protected $description = [];

    /**
     * @var array
     */
    protected $requestMiddleware = [
        CorrelationID::class
    ];

    /**
     * @var array
     */
    protected $responseMiddleware = [

    ];

    /**
     * I'd love to inject the client here, but we can't set/change the stack after it is instantiated.
     * Requiring us to instantiate ourselves, after building our stack.
     */
    public function __construct()
    {
        $this->client = new GuzzleClient(['handler' => $this->buildStack()]);
    }

    /**
     * Sets up the stack, including attaching our middleware
     */
    protected function buildStack()
    {
        $stack = new HandlerStack();

        // I assume this is what we just want?
        $stack->setHandler(new CurlHandler());

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
     * Figure out if the called method is a defined SDK function, and handle it!
     *
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments) {
        if(!array_key_exists($name, $this->description)) {
            throw new \InvalidArgumentException("Undefined method: $name");
        }

        return $this->handle($this->description[$name], $arguments);
    }

    /**
     * Real work begins here
     *
     * @param $config
     * @param $arguments
     */
    private function handle($config, $arguments)
    {
        /*
         * 1. Validate arguments against config
         * 2. Build the body payload
         * 3. Build the URI (which may have variable substitution)
         * 4. Prepare and send the Guzzle request
         * 5. Unserialize the response
         */

        return $unserializedResponse;
    }
}