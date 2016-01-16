<?php namespace RC\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use PHPUnit_Framework_TestCase;

class AbstractServiceTest extends PHPUnit_Framework_TestCase
{
    // tests
    public function testInstantiation()
    {
        $container = new Container();
        $client = new HttpClient($container);
        $pipeline = new Pipeline($container);
        $abstractServiceStub = $this->getMockForAbstractClass(AbstractService::class, [$client, $pipeline], 'MockedAbstractService');
        $this->assertEquals('MockedAbstractService', get_class($abstractServiceStub));
    }

    public function testCreation()
    {
        $abstractServiceStub = $this->getMockForAbstractClass(AbstractService::class, [], 'MockedAbstractService', false);
        $createdAbstractServiceStub = $abstractServiceStub::create('fartymakblarty');
        $this->assertEquals('MockedAbstractService', get_class($createdAbstractServiceStub));
        $createdAbstractServiceStub2 = $abstractServiceStub->create();
        $this->assertEquals('MockedAbstractService', get_class($createdAbstractServiceStub2));
    }

    public function testCall()
    {
        $testBody = ["test" => "results"];
        $testDouble = $this->getTestDouble($testBody );
        $testDouble->setKey('fartymakblarty');
        $this->assertEquals('RC\Sdk\AbstractServiceTestDouble', get_class($testDouble));
        $result = $testDouble->test1(['domain' => 'myunitdomain.php', 'id' => 77, 'name' => 'phpunit']);
        $this->assertEquals($testBody, $result, 'We should have gotten our test body back');
    }

    public function testGetSetKey(){
        $key = 'fartymakblarty';
        $testBody = ["test" => "results"];
        $testDouble = $this->getTestDouble($testBody );
        $testDouble->setKey(null);
        $this->assertEquals('RC\Sdk\AbstractServiceTestDouble', get_class($testDouble));
        $this->setExpectedException('RC\Sdk\Exceptions\KeyNotFoundException');
        $testDouble->test1(['domain' => 'myunitdomain.php', 'id' => 77, 'name' => 'phpunit']);
    }

    public function testBadCall(){
        $testBody = ["test" => "results"];
        $testDouble = $this->getTestDouble($testBody );
        $this->assertEquals('RC\Sdk\AbstractServiceTestDouble', get_class($testDouble));

        $this->setExpectedException('InvalidArgumentException');
        $testDouble->test2([]);
    }

    protected function getTestDouble($testBody){
        $container = new Container();
        $pipeline = new Pipeline($container);

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($testBody)),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $httpClient = new HttpClient($container);
        $httpClient->setGuzzle($client);

        return new AbstractServiceTestDouble($httpClient, $pipeline);
    }
}

class AbstractServiceTestDouble extends AbstractService {
    /**
     * @var string
     */
    protected $baseUrl = "http://unittest.php";

    /**
     * @var array
     */
    public $description = [
        "test1" => [
            "httpMethod" => "POST",
            "uri" => "/test/test1",
            "parameters" => [
                "domain" => [
                    "validate" => "required|string",
                    "location" => "body"
                ],
                "id" => [
                    "validate" => "required|numeric",
                    "location" => "uri"
                ],
                "name" => [
                    "validate" => "string",
                    "location" => "body"
                ]
            ]
        ]
    ];
}