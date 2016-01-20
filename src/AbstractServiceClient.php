<?php
namespace RC\Sdk;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Pipeline\Pipeline;
use RC\Sdk\Service\Description;
use RC\Sdk\Service\Operation;
use RC\Sdk\Exceptions\KeyNotFoundException;
use RC\Sdk\Pipeline\AddCorrelationID;
use RC\Sdk\Pipeline\BuildBody;
use RC\Sdk\Pipeline\BuildUri;
use RC\Sdk\Pipeline\AddSignature;
use RC\Sdk\Pipeline\HandleExceptions;
use RC\Sdk\Pipeline\PipeInterface;
use RC\Sdk\Pipeline\SendRequest;
use RC\Sdk\Pipeline\ValidateArguments;
use ReflectionClass;

/**
 * Class AbstractClient
 * @package Sdk
 */
abstract class AbstractServiceClient
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
    ];

    /**
     * AbstractClient constructor.
     *
     * @param ClientInterface $client
     * @param Pipeline   $pipeline
     */
    public function __construct(ClientInterface $client, Pipeline $pipeline)
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
     * @return AbstractServiceClient
     */
    public static function create($key = null)
    {
        if(!container()->bound('GuzzleHttp\ClientInterface')) {
            container()->bind('GuzzleHttp\ClientInterface', 'GuzzleHttp\Client');
        }

        $instance = container()->make(static::class);

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
     * @param PipeInterface $pipe
     */
    public function addPipe(PipeInterface $pipe)
    {
        $this->pipes[] = $pipe;
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
        return $this->pipeline->send($request)
            ->through($this->pipes)
            ->then(function ($request) {
                try {
                    $response = $request->send();

                    // Try to decode it
                    $body = (string) $response->getBody();
                    if(is_array(json_decode($body, true))) {
                        $body = json_decode($body, true);
                    }

                    return $body;

                } catch(ClientException $e) {
                    (new ErrorHandler())->handle($e, $this->getDescription()->getErrorHandlers());
                }
            });
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
            throw new \InvalidArgumentException("Description config hasn't been provided");
        }

        return $this->description;
    }
}