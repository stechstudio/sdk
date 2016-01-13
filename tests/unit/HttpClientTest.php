<?php
use RC\Sdk\HttpClient;

class HttpClientTest extends \Codeception\TestCase\Test
{
    use \Codeception\Specify;

    /**
     * @var HttpClient
     */
    private $httpClient;

    // tests
    public function testInstantiation()
    {
        $this->httpClient = new HttpClient();

        $this->specify("No Request Middleware", function(){
         $this->assertEmpty($this->httpClient->getRequestMiddleware());
        });

        $this->specify("No Response Middleware", function(){
            $this->assertEmpty($this->httpClient->getResponseMiddleware());
        });

        $this->specify("Auto Generates a Guzzle Client", function(){
            $this->assertEquals(GuzzleHttp\Client::class, get_class($this->httpClient->getGuzzle()));
        });
    }

    
}