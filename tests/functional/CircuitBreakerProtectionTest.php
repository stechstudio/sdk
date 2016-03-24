<?php
namespace STS\Sdk;

use STS\Sdk\Exceptions\ServiceUnavailableException;

class CircuitBreakerProtectionTest extends \PHPUnit_Framework_TestCase
{
    protected $description = [
        'name' => 'Test',
        'baseUrl' => 'http://mockbin.org',
        'cache' => [
            'driver' => [
                'name' => 'Ephemeral',
                'options' => []
            ]
        ],
        'circuitBreaker' => [],
        'operations' => [
            'success' => [
                'httpMethod' => 'GET',
                'uri' => '/bin/f738e274-ba99-4405-accd-5bfb0358f27b'
            ],
            'failure' => [
                'httpMethod' => 'GET',
                'uri' => '/bin/3d5b8a1a-48a3-47e9-8fe8-88a3887d99ef'
            ]
        ]
    ];

    public function testDescriptionHasCircuitBreaker()
    {
        $client = new Client($this->description);

        $this->assertTrue($client->getDescription()->wantsCircuitBreaker());
        $this->assertTrue($client->getCircuitBreaker() instanceof CircuitBreaker);
    }

    public function testTripCircuitBreaker()
    {
        $client = new Client($this->description);
        $client->getCircuitBreaker()->setFailureThreshold(3);

        $this->callFailureAndSuppress($client);
        $this->callFailureAndSuppress($client);

        $this->assertEquals(2, $client->getCircuitBreaker()->getFailures());
        $this->assertTrue($client->isAvailable());

        $this->callFailureAndSuppress($client);
        $this->assertFalse($client->isAvailable());
    }

    protected function callFailureAndSuppress($client)
    {
        try {
            $client->failure();
        } catch(ServiceUnavailableException $e) {}
    }
}
