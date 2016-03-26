<?php
namespace STS\Sdk;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Pipeline\Pipeline;
use Stash\Pool;
use STS\Sdk\Pipeline\CacheFallback;
use STS\Sdk\Pipeline\CircuitBreakerProtection;
use STS\Sdk\Pipeline\HandleError;
use STS\Sdk\Pipeline\SendRequest;
use STS\Sdk\Service\Description;
use STS\Sdk\Pipeline\BuildBody;
use STS\Sdk\Pipeline\BuildUri;
use STS\Sdk\Pipeline\ValidateArguments;

/**
 * Class Client
 * @package Sdk
 */
class Client
{
    /**
     * @var string
     */
    protected $name = null;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var Pipeline
     */
    protected $pipeline;

    /**
     * @var Description
     */
    protected $description;

    /**
     * The order of some of these pipes is pretty important.
     * Don't re-order these unless you've really thought through it.
     *
     * @var array
     */
    protected $pipes = [
        ValidateArguments::class,
        BuildBody::class,
        BuildUri::class,
        CacheFallback::class,
        CircuitBreakerProtection::class,
        HandleError::class
    ];

    /**
     * @param null $description
     */
    public function __construct($description = null)
    {
        if(is_array($description) || $description instanceof Description) {
            $this->setDescription($description);
        }
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
        return $this->name == null
            ? $this->description->getName()
            : $this->name;
    }

    /**
     * @param ClientInterface $client
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @return ClientInterface
     */
    public function getClient()
    {
        if($this->client == null) {
            $this->client = new \GuzzleHttp\Client();
        }
        return $this->client;
    }

    /**
     * @return mixed
     */
    protected function getPipeline()
    {
        return make(Pipeline::class);
    }

    /**
     * @param string $pipe
     */
    public function appendPipe($pipe)
    {
        $this->pipes[] = $pipe;
    }

    /**
     * @param $pipe
     */
    public function prependPipe($pipe)
    {
        array_unshift($this->pipes, $pipe);
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
        if (!$this->getDescription()->hasOperation($name)) {
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
    public function prepareRequest($name, $data)
    {
        return new Request(
            $this->getClient(),
            $this->getName(),
            $this->getDescription(),
            $this->getDescription()->getOperation($name, $data),
            $data
        );
    }

    /**
     * Real work begins here
     *
     * @param $request
     *
     * @return array
     */
    protected function handle($request)
    {
        return $this->getPipeline()
            ->send($request)
            ->through($this->pipes)
            ->then(function($request) {
                $response = $request->send();

                // Try to decode it
                $body = (string) $response->getBody();
                if(is_array(json_decode($body, true))) {
                    $body = json_decode($body, true);
                }

                return $body;
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
     * @return Pool
     */
    public function getCachePool()
    {
        return $this->getDescription()->getCachePool();
    }

    /**
     * @return CircuitBreaker
     */
    public function getCircuitBreaker()
    {
        return $this->getDescription()->getCircuitBreaker();
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->getDescription()->wantsCircuitBreaker()
            ? $this->getCircuitBreaker()->isAvailable()
            : true;
    }

    /**
     * @return Description
     */
    public function getDescription()
    {
        if($this->description == null) {
            throw new \InvalidArgumentException("Description config hasn't been provided");
        }

        return $this->description;
    }
}
