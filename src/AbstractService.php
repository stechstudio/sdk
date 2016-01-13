<?php
namespace RC\Sdk;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use RC\Sdk\Middleware\CorrelationID;

/**
 * Class AbstractClient
 * @package Sdk
 */
class AbstractService
{
    /**
     * @var GuzzleClient
     */
    protected $client;

    /**
     * @var Pipeline
     */
    protected $pipeline;

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
     * AbstractClient constructor.
     *
     * @param HttpClient $client
     * @param Pipeline   $pipeline
     */
    public function __construct(HttpClient $client, Pipeline $pipeline)
    {
        $this->client = $client;
        $this->client->setRequestMiddleware($this->requestMiddleware);
        $this->client->setResponseMiddleware($this->requestMiddleware);

        $this->pipeline = $pipeline;
    }

    /**
     * @return AbstractService
     */
    public static function create()
    {
        return new static(new HttpClient(), new Pipeline(new Container()));
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

        $client = $this->getClient();

        return $unserializedResponse;
    }

    /**
     * @return GuzzleClient
     */
    protected function getClient()
    {
        return $this->client;
    }
}