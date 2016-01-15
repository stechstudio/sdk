<?php
namespace RC\Sdk;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Pipeline\Pipeline;
use RC\Sdk\Exceptions\KeyNotFoundException;
use RC\Sdk\Pipeline\AddCorrelationID;
use RC\Sdk\Pipeline\BuildBody;
use RC\Sdk\Pipeline\BuildUri;
use RC\Sdk\Pipeline\AddSignature;
use RC\Sdk\Pipeline\HandleExceptions;
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
     * @var array
     */
    protected $operations = null;

    /**
     * @var Pipeline
     */
    protected $pipeline;

    /**
     * @var array
     */
    protected $description = null;

    /**
     * @var array
     */
    protected $pipes = [
        ValidateArguments::class,
        BuildBody::class,
        BuildUri::class,
        AddSignature::class,
        AddCorrelationID::class,
        SendRequest::class,
        HandleExceptions::class
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
        $this->pipeline = $pipeline;

        if ($this->name == null) {
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
        $instance = new static(new HttpClient(), new Pipeline(container()));

        if ($key != null) {
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
        if ($this->key != null) {
            return $this->key;
        }

        // See if we have one as an environment variable
        if (getenv(strtoupper($this->getName() . "_KEY")) !== false) {
            return getenv(strtoupper($this->getName() . "_KEY"));
        }

        throw new KeyNotFoundException();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Figure out if the called method is a defined SDK function, and handle it!
     *
     * @param $name
     * @param $arguments
     *
     * @return array
     */
    public function __call($name, $arguments)
    {
        if (!array_key_exists($name, $this->getOperations())) {
            throw new \InvalidArgumentException("Undefined method: $name");
        }

        return $this->handle($this->prepareRequest($this->getOperations()[$name], $arguments[0]));
    }

    /**
     * @param $config
     * @param $arguments
     *
     * @return Request
     */
    protected function prepareRequest($config, $arguments)
    {
        return new Request($this->getClient(), $this->getName(), $this->getKey(), $this->baseUrl, $config, $arguments);
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
        return $this->pipeline->send($request)
            ->through($this->pipes)
            ->then(function ($request) {
                return $request->getResponseBody();
            });
    }

    /**
     * @return HttpClient
     */
    protected function getClient()
    {
        return $this->client;
    }

    protected function getOperations()
    {
        if($this->operations == null) {
            $this->loadDescription();
        }

        return $this->operations;
    }

    /**
     * @return array
     */
    protected function getDescription()
    {
        if($this->description == null) {
            $this->description = $this->loadDescription();
        }

        return $this->description;
    }

    /**
     * @return mixed
     * @throws FileNotFoundException
     */
    protected function loadDescription()
    {
        $descriptionFile = __DIR__ . "/Service/" . $this->getName() . "/description.php";

        if(!file_exists($descriptionFile)) {
            throw new FileNotFoundException("Description file not found for service " . $this->getName());
        }

        $this->description = include($descriptionFile);

        $this->baseUrl = $this->description['baseUrl'];
        $this->operations = $this->description['operations'];
    }
}