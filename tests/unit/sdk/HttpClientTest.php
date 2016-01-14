<?php
use Illuminate\Container\Container;
use RC\Sdk\HttpClient;
use RC\Sdk\Middleware\CorrelationID;

/**
 * Created by PhpStorm.
 * User: Bubba
 * Date: 1/14/2016
 * Time: 9:42 AM
 */
class HttpClientTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    // tests
    public function testInstantiation()
    {
        $this->httpClient = new HttpClient(new Container());
        $this->assertEmpty($this->httpClient->getRequestMiddleware(), 'There should be no request middleware');
        $this->assertEmpty($this->httpClient->getResponseMiddleware(), 'There should be no response middleware');
        $this->assertEquals(GuzzleHttp\Client::class, get_class($this->httpClient->getGuzzle()), 'We should get a guzzle client');
    }

    public function testAddingRequestMiddleware(){
        $this->httpClient = new HttpClient(new Container());
        $this->httpClient->addRequestMiddleware(CorrelationID::class);
        $requestMiddlewareStack = $this->httpClient->getRequestMiddleware();
        $this->assertEquals(1, count($requestMiddlewareStack), 'Should only be one item on the stack');
        $this->assertEquals(GuzzleHttp\Client::class, get_class($this->httpClient->getGuzzle()), 'We should get a valid guzzle client');
    }

    public function testSettingRequestMiddleware(){
        $this->httpClient = new HttpClient(new Container());
        $this->httpClient->setRequestMiddleware([CorrelationID::class]);
        $requestMiddlewareStack = $this->httpClient->getRequestMiddleware();
        $this->assertEquals(1, count($requestMiddlewareStack), 'Should only be one item on the stack');
        $this->assertEquals(GuzzleHttp\Client::class, get_class($this->httpClient->getGuzzle()), 'We should get a valid guzzle client');
    }

    public function testAddingResponseMiddleware(){
        $this->httpClient = new HttpClient(new Container());
        $this->httpClient->addResponseMiddleware(new CorrelationID());
        $responseMiddlewareStack = $this->httpClient->getResponseMiddleware();
        $this->assertEquals(1, count($responseMiddlewareStack), 'Should only be one item on the stack');
        $this->assertEquals(GuzzleHttp\Client::class, get_class($this->httpClient->getGuzzle()), 'We should get a valid guzzle client');
    }

    public function testSettingResponseMiddleware(){
        $this->httpClient = new HttpClient(new Container());
        $this->httpClient->setResponseMiddleware([CorrelationID::class]);
        $responseMiddlewareStack = $this->httpClient->getResponseMiddleware();
        $this->assertEquals(1, count($responseMiddlewareStack), 'Should only be one item on the stack');
        $this->assertEquals(GuzzleHttp\Client::class, get_class($this->httpClient->getGuzzle()), 'We should get a valid guzzle client');
    }

    public function testCallable(){
        $this->httpClient = new HttpClient(new Container());
        $result = $this->httpClient->getConfig(null);
        $this->assertEquals('GuzzleHttp\HandlerStack', get_class($result['handler']), 'This should be a guzzle handler stack');
    }
}
