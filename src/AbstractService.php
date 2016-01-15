<?php
namespace RC\Sdk;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use RC\Sdk\Exceptions\KeyNotFoundException;
use RC\Sdk\Middleware\CorrelationID;
use RC\Sdk\Middleware\RequestSignature;
use RC\Sdk\Pipeline\BuildBody;
use RC\Sdk\Pipeline\BuildUrl;
use RC\Sdk\Pipeline\CreateSignature;
use RC\Sdk\Pipeline\SendRequest;
use RC\Sdk\Pipeline\ValidateArguments;
use ReflectionClass;

/**
 * Class AbstractClient
 * @package Sdk
 */
abstract class AbstractService
{
    /**
     * @var string
     */
    protected $name = null;
    /**
     * @var string|null
     */
    protected $key = null;

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
        CreateSignature::class,
        SendRequest::class
    ];

    /**
     * @var null
     */
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

        if($this->name == null) {
            $this->name = (new ReflectionClass($this))->getShortName();
        }
    }

    /**
     * @param null $key
     *
     * @return AbstractService
     */
    public static function create($key = null)
    {
        $container = new Container();
        $instance = new static(new HttpClient($container), new Pipeline($container));

        if($key != null) {
            $instance->setKey($key);
        }

        return $instance;
    }

    /**
     * @param $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * @return null|string
     * @throws KeyNotFoundException
     */
    protected function getKey()
    {
        if($this->key != null) {
            return $this->key;
        }

        // See if we have one as an environment variable
        if(getenv(strtoupper($this->name . "_KEY")) !== false) {
            return getenv(strtoupper($this->name . "_KEY"));
        }

        throw new KeyNotFoundException();
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
        return new Request($this->getClient(), $this->getKey(), $this->baseUrl, $config, $arguments);
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