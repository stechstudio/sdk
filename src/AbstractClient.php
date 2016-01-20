<?php
namespace STS\Sdk;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Pipeline\Pipeline;
use STS\Sdk\Service\Description;
use STS\Sdk\Exceptions\KeyNotFoundException;
use STS\Sdk\Pipeline\AddCorrelationID;
use STS\Sdk\Pipeline\BuildBody;
use STS\Sdk\Pipeline\BuildUri;
use STS\Sdk\Pipeline\AddSignature;
use STS\Sdk\Pipeline\PipeInterface;
use STS\Sdk\Pipeline\ValidateArguments;

/**
 * Class AbstractClient
 * @package Sdk
 */
abstract class AbstractClient
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
    protected $pipes = [
        ValidateArguments::class,
        BuildBody::class,
        BuildUri::class,
    ];

    /**
     * @return AbstractClient
     */
    public static function create()
    {
        if(!container()->bound('GuzzleHttp\ClientInterface')) {
            container()->bind('GuzzleHttp\ClientInterface', 'GuzzleHttp\Client');
        }

        $instance = new static();
        $instance->setClient(container()->make('GuzzleHttp\ClientInterface'));

        return $instance;
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
        return new Request($this->getClient(), $this->getName(), $this->getDescription(), $this->getDescription()->getOperation($name, $data), $data);
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
        return $this->getPipeline()->send($request)
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