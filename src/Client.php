<?php
namespace STS\Sdk;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Pipeline\Pipeline;
use Stash\Pool;
use STS\Sdk\Pipeline\CacheFallback;
use STS\Sdk\Pipeline\CircuitBreakerProtection;
use STS\Sdk\Pipeline\HandleError;
use STS\Sdk\Pipeline\LogRequest;
use STS\Sdk\Pipeline\ModelResponse;
use STS\Sdk\Pipeline\SendRequest;
use STS\Sdk\Service;
use STS\Sdk\Pipeline\BuildBody;
use STS\Sdk\Pipeline\BuildUri;
use STS\Sdk\Pipeline\ValidateArguments;
use STS\Sdk\Service\CircuitBreaker;

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
     * @var Service
     */
    protected $service;

    /**
     * The order of some of these pipes is pretty important.
     * Don't re-order these unless you've really thought through it.
     *
     * @var array
     */
    protected $pipes = [
        //ValidateArguments::class,
        BuildBody::class,
        BuildUri::class,
        ModelResponse::class,
        CacheFallback::class,
        LogRequest::class,
        CircuitBreakerProtection::class,
        HandleError::class
    ];

    /**
     * @param null $service
     */
    public function __construct($service = null)
    {
        if(is_array($service) || $service instanceof Service) {
            $this->setService($service);
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
            ? $this->service->getName()
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
        return new Pipeline(container());
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
        if (!$this->getService()->hasOperation($name)) {
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
            $this->getService(),
            $this->getService()->getOperation($name, $data),
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
        $pipes = array_merge(
            $this->getService()->getPrependedPipes(),
            $this->pipes,
            $this->getService()->getAppendedPipes()
        );

        return $this->getPipeline()
            ->send($request)
            ->through($pipes)
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
     * @param $service
     */
    public function setService($service)
    {
        if($service instanceof Service) {
            $this->service = $service;
        } else {
            $this->service = new Service($service);
        }
    }

    /**
     * @return Pool
     */
    public function getCachePool()
    {
        return $this->getService()->getCachePool();
    }

    /**
     * @return CircuitBreaker
     */
    public function getCircuitBreaker()
    {
        return $this->getService()->getCircuitBreaker();
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->getService()->wantsCircuitBreaker()
            ? $this->getCircuitBreaker()->isAvailable()
            : true;
    }

    /**
     * @return Service
     */
    public function getService()
    {
        if($this->service == null) {
            throw new \InvalidArgumentException("Service hasn't been provided");
        }

        return $this->service;
    }
}
