<?php
namespace Tests;

use Closure;
use Stash\Pool;
use STS\Sdk\Client;
use STS\Sdk\Request;
use STS\Sdk\Service;
use InvalidArgumentException;
use GuzzleHttp\Client AS GuzzleClient;
use STS\Sdk\Service\CircuitBreaker;

class ClientTest extends TestCase
{
    protected $description = [
        'name' => 'test',
        'baseUrl' => 'http://mockbin.org/bin',
        'operations' => [
            'getOk' => [
                'httpMethod' => 'GET',
                'uri' => '/f738e274-ba99-4405-accd-5bfb0358f27b'
            ]
        ]
    ];

    public function testCreateWithDescription()
    {
        // Just pass in the array
        $client = new Client($this->description);
        $this->assertTrue($client->getService() instanceof Service);
        $this->assertEquals($client->getService()->getBaseUrl(), 'http://mockbin.org/bin');

        // Now pass in a Description instance
        $client = new Client(new Service($this->description));
        $this->assertTrue($client->getService() instanceof Service);
        $this->assertEquals($client->getService()->getBaseUrl(), 'http://mockbin.org/bin');
    }

    public function testCreateWithoutDescription()
    {
        $client = new Client();

        $this->expectException(InvalidArgumentException::class);
        $client->getService();
    }

    public function testSetGetName()
    {
        $client = new Client();
        $client->setName("foo");

        $this->assertEquals($client->getName(), "foo");
    }

    public function testSetGetClient()
    {
        $client = new Client();

        // Without providing a client, we should just get a GuzzleClient built for us
        $this->assertTrue($client->getClient() instanceof GuzzleClient);

        $client = new Client();
        $client->setClient(new GuzzleClient(["base_uri" => "http://foo.local"]));

        // Now we should have our custom client
        $this->assertTrue($client->getClient() instanceof GuzzleClient);
        $this->assertEquals($client->getClient()->getConfig("base_uri"), "http://foo.local");
    }

    public function testInvalidOperation()
    {
        $client = new Client($this->description);

        $this->expectException(InvalidArgumentException::class);
        $client->foo();
    }

    public function testAddPipes()
    {
        // First append a pipe
        $client = new Client($this->description);
        $client->appendPipe(Pipe1::class);

        $this->assertEquals("inside pipe1", $client->getOk());

        // Now append the second pipe, we should still have the first pipe responding
        $client->appendPipe(Pipe2::class);
        $this->assertEquals("inside pipe1", $client->getOk());

        // But if we prepend the second pipe...
        $client->prependPipe(Pipe2::class);
        $this->assertEquals("inside pipe2", $client->getOk());
    }

    public function testNoCachePool()
    {
        $client = new Client($this->description);

        $this->expectException(\InvalidArgumentException::class);
        $client->getCachePool();
    }

    public function testValidCachePool()
    {
        $description = $this->description;
        $description['cache'] = [
            'driver' => [
                'name' => 'Ephemeral',
                'options' => []
            ]
        ];

        $client = new Client($description);
        $this->assertTrue($client->getCachePool() instanceof Pool);
    }

    public function testNoCircuitBreaker()
    {
        $client = new Client($this->description);

        $this->assertTrue($client->isAvailable());

        $this->expectException(\InvalidArgumentException::class);
        $client->getCircuitBreaker();
    }

    public function testHasCircuitBreaker()
    {
        $description = $this->description;
        $description['cache'] = [
            'driver' => [
                'name' => 'Ephemeral',
                'options' => []
            ]
        ];
        $description['circuitBreaker'] = [
            'failureThreshold' => 10
        ];

        $client = new Client($description);

        $this->assertTrue($client->isAvailable());
        $this->assertTrue($client->getCircuitBreaker() instanceof CircuitBreaker);

        $client->getCircuitBreaker()->trip();
        $this->assertFalse($client->isAvailable());
    }
}

class Pipe1
{
    public function handle(Request $request, Closure $next)
    {
        return "inside pipe1";
    }
}

class Pipe2
{
    public function handle(Request $request, Closure $next)
    {
        return "inside pipe2";
    }
}
