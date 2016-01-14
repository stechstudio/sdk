<?php
use RC\Sdk\HttpClient;
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
        $this->httpClient = new HttpClient();
            $this->assertEmpty($this->httpClient->getRequestMiddleware());
            $this->assertEmpty($this->httpClient->getResponseMiddleware());
            $this->assertEquals(GuzzleHttp\Client::class, get_class($this->httpClient->getGuzzle()));
    }
}
