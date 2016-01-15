<?php
namespace RC\Sdk;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use RC\Sdk\Middleware\CorrelationID;
use RC\Sdk\Pipeline\BuildBody;
use RC\Sdk\Pipeline\BuildUrl;
use RC\Sdk\Pipeline\SendRequest;
use RC\Sdk\Pipeline\ValidateArguments;

/**
 * Class AbstractClient
 * @package Sdk
 */
abstract class AbstractService
{
    /**
     * @var HttpClient
     */
    protected $client;

    /**
     * @var string|null
     */
    protected $baseUrl = null;

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
    protected $responseMiddleware = [];

    /**
     * @var array
     */
    protected $pipes = [
        ValidateArguments::class,
        BuildBody::class,
        BuildUrl::class,
        SendRequest::class
    ];

    protected $result = null;

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
        $this->client->setResponseMiddleware($this->responseMiddleware);

        $this->pipeline = $pipeline;
    }

    /**
     * @return AbstractService
     */
    public static function create()
    {
        $container = new Container();
        return new static(new HttpClient($container), new Pipeline($container));
    }

    /**
     * Figure out if the called method is a defined SDK function, and handle it!
     *
     * @param $name
     * @param $arguments
     *
     * @return array
     */
    public function __call($name, $arguments) {
        if(!array_key_exists($name, $this->getDescription())) {
            throw new \InvalidArgumentException("Undefined method: $name");
        }

        return $this->handle($this->prepareRequest($this->getDescription()[$name], $arguments[0]));
    }

    /**
     * @param $config
     * @param $arguments
     *
     * @return Request
     */
    protected function prepareRequest($config, $arguments)
    {
        return new Request($this->getClient(), $this->baseUrl, $config, $arguments);
    }

    /**
     * Real work begins here
     *
     * @param $request
     *
     * @return array
     */
    private function handle($request)
    {
        $this->pipeline->send($request)
            ->through($this->pipes)
            ->then(function($request) {
                $this->result = $request->responseBody;
            });

        return $this->result;
    }

    /**
     * @return HttpClient
     */
    protected function getClient()
    {
        return $this->client;
    }

    /**
     * @return array
     */
    protected function getDescription()
    {
        return $this->description;
    }
}