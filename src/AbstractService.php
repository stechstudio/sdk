<?php
namespace RC\Sdk;

use GuzzleHttp\Exception\ClientException;
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
     * @var Operation
     */
    protected $operation;

    /**
     * @var Pipeline
     */
    protected $pipeline;

    /**
     * @var Description
     */
    protected $description;

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
        //HandleExceptions::class
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
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return HttpClient
     */
    protected function getClient()
    {
        return $this->client;
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
        if (!$this->getDescription()->getOperation($name)) {
            throw new \InvalidArgumentException("Undefined method: $name");
        }

        $data = (isset($arguments[0])) ? $arguments[0] : [];

        return $this->handle($this->prepareRequest($name, $data));
    }

    /**
     * @param $name
     * @param $data
     *
     * @return Request
     */
    protected function prepareRequest($name, $data)
    {
        return new Request($this->getClient(), $this->getName(), $this->getKey(), $this->getDescription(), $this->getDescription()->getOperation($name, $data), $data);
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
        try {
            return $this->pipeline->send($request)
                ->through($this->pipes)
                ->then(function ($request) {
                    return $request->getResponseBody();
                });
        } catch(ClientException $e) {
            (new ErrorHandler())->handle($this->getName(), $e);
        }
    }

    /**
     * @param $description
     */
    public function setDescription($description)
    {
        if($description instanceof Description) {
            $this->description = $description;
        } else {
            $this->description = new Description($description);
        }
    }

    /**
     * @return array
     */
    public function getDescription()
    {
        if($this->description == null) {
            $this->description = $this->loadDescriptionFromFile();
        }

        return $this->description;
    }

    /**
     * @return mixed
     * @throws FileNotFoundException
     */
    protected function loadDescriptionFromFile()
    {
        $descriptionFile = __DIR__ . "/Service/" . $this->getName() . "/description.php";

        return Description::loadFromFile($descriptionFile);
    }
}