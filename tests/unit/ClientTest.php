<?php
namespace STS\Sdk;

use STS\Sdk\Service\Description;
use InvalidArgumentException;
use GuzzleHttp\Client AS GuzzleClient;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    protected $description = [
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
        $this->assertTrue($client->getDescription() instanceof Description);
        $this->assertEquals($client->getDescription()->getBaseUrl(), 'http://mockbin.org/bin');

        // Now pass in a Description instance
        $client = new Client(new Description($this->description));
        $this->assertTrue($client->getDescription() instanceof Description);
        $this->assertEquals($client->getDescription()->getBaseUrl(), 'http://mockbin.org/bin');
    }

    public function testCreateWithoutDescription()
    {
        $client = new Client();

        $this->setExpectedException(InvalidArgumentException::class);
        $client->getDescription();
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

        $this->setExpectedException(InvalidArgumentException::class);
        $client->foo();
    }
}