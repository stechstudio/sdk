<?php
namespace STS\Sdk;

use GuzzleHttp\Exception\ClientException;
use STS\Sdk\Exceptions\CircuitBreakerOpenException;
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
        'circuitBreaker' => [
            'failureThreshold' => 3,
            'autoRetryInterval' => 1
        ],
        'operations' => [
            'success' => [
                'httpMethod' => 'GET',
                'uri' => '/bin/f738e274-ba99-4405-accd-5bfb0358f27b'
            ],
            'failure' => [
                'httpMethod' => 'GET',
                'uri' => '/bin/3d5b8a1a-48a3-47e9-8fe8-88a3887d99ef'
            ],
            'error400WithNoBody' => [
                'httpMethod' => 'GET',
                'uri' => '/bin/0704da9e-bdab-40e8-8ac2-7a76bae5f7fa'
            ]
        ]
    ];

    public function testDescriptionHasCircuitBreaker()
    {
        $client = new Client($this->description);

        $this->assertTrue($client->getDescription()->wantsCircuitBreaker());
        $this->assertTrue($client->getCircuitBreaker() instanceof CircuitBreaker);
    }

    public function testFailureCausesException()
    {
        $client = new Client($this->description);

        $this->setExpectedException(ServiceUnavailableException::class);

        $client->failure();
    }

    public function test400ErrorDoesNotCauseCircuitBreakerException()
    {
        // We want to ensure that a 4XX error (instead of 5XX) is unhandled by circuit breaker,
        // and our normal error parsing/handling takes over

        $client = new Client($this->description);

        $this->setExpectedException(ClientException::class);

        $client->error400WithNoBody();
    }

    public function testTripCircuitBreaker()
    {
        $client = new Client($this->description);

        // First make sure we're operational
        $this->assertEquals("ok", $client->success());

        // Call the failing endpoint twice, which is less than our threshold
        $this->callFailureAndSuppress($client);
        $this->callFailureAndSuppress($client);

        // We should still be available
        $this->assertEquals(2, $client->getCircuitBreaker()->getFailures());
        $this->assertTrue($client->isAvailable());

        // One more failure will trip
        $this->callFailureAndSuppress($client);
        $this->assertFalse($client->isAvailable());

        // We should be available after the retry interval
        sleep($client->getCircuitBreaker()->getAutoRetryInterval());
        $this->assertTrue($client->getCircuitBreaker()->isAvailable());

        // And now just one more failure
        $this->callFailureAndSuppress($client);

        $this->assertFalse($client->isAvailable());

        // Since we're unavailable, we will now get a CircuitBreakerOpenException
        $this->setExpectedException(CircuitBreakerOpenException::class);
        $client->success();
    }



    protected function callFailureAndSuppress($client)
    {
        try {
            $client->failure();
        } catch(ServiceUnavailableException $e) {}
    }
}
