<?php
namespace STS\Sdk;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Pipeline\Pipeline;
use Stash\Pool;
use STS\Sdk\CircuitBreaker\BreakerPanel;
use STS\Sdk\CircuitBreaker\BreakerSwitch;
use STS\Sdk\Pipeline\Caching;
use STS\Sdk\Pipeline\CircuitBreaker;
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
     * @var array
     */
    protected $basePipes = [
        ValidateArguments::class,
        BuildBody::class,
        BuildUri::class,
    ];

    /**
     * @var array
     */
    protected $servicePipes = [];

    /**
     * @var Pool
     */
    protected $cachePool;

    /**
     * @var BreakerPanel
     */
    protected $breakerPanel;

    /**
     * @var BreakerSwitch
     */
    protected $breakerSwitch;

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
        return $this->name;
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
        return container()->make(Pipeline::class);
    }

    /**
     * @param string $pipe
     */
    public function appendPipe($pipe)
    {
        $this->basePipes[] = $pipe;
    }

    /**
     * @param $pipe
     */
    public function prependPipe($pipe)
    {
        array_unshift($this->basePipes, $pipe);
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
        return new Request(
            $this->getClient(),
            $this->getName(),
            $this->getDescription(),
            $this->getDescription()->getOperation($name, $data),
            $data,
            $this->cachePool,
            $this->breakerPanel
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
        return $this->getPipeline()->send($request)
            ->through(array_merge($this->basePipes, $this->servicePipes))
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
                    (new ErrorHandler())->handleClientError($e, $this->getDescription()->getErrorHandlers());
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

        $this->initServicePipeline();
    }

    /**
     * Setup the service-specific pipeline pipes
     */
    protected function initServicePipeline()
    {
        // Clear it out, in case we had a previously loaded service
        $this->servicePipes = [];

        if($this->getDescription()->wantsCache()) {
            $this->cachePool = new Pool($this->getDescription()->getCacheDriver());
            $this->servicePipes[] = Caching::class;
        }

        if($this->getDescription()->wantsCache() && $this->getDescription()->wantsCircuitBreaker()) {
            $this->breakerPanel = (new BreakerPanel())->setCachePool($this->cachePool);
            $this->breakerSwitch = $this->breakerPanel->get($this->getName());

            $this->servicePipes[] = CircuitBreaker::class;
        }
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
