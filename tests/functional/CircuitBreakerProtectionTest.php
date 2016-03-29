<?php
namespace STS\Sdk;

use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;
use STS\Sdk\Exceptions\CircuitBreakerOpenException;
use STS\Sdk\Exceptions\ServiceErrorException;
use STS\Sdk\Exceptions\ServiceResponseException;
use STS\Sdk\Exceptions\ServiceUnavailableException;
use STS\Sdk\Service\CircuitBreaker;

class CircuitBreakerProtectionTest extends \PHPUnit_Framework_TestCase
{
    protected $description = [
        'name' => 'Test',
        'baseUrl' => 'http://mockbin.org',
        'cache' => [
            'driver' => [
                'name' => 'Ephemeral',
                'options' => []
            ],
            'namespace' => 'testing'
        ],
        'circuitBreaker' => [
            'failureThreshold' => 3,
            'autoRetryInterval' => 1
        ],
        'operations' => [
            'success' => [
                'cache' => [
                    // We have to explicitly disable fallback caching here to accurately test the circuit breaker
                    'fallback' => false
                ],
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
            ],
            'remoteErrorWithDefaultException' => [
                'httpMethod' => 'GET',
                'uri' => '/bin/e59a596a-5965-4e00-a1f8-f50474ddd9d3'
            ],
        ]
    ];

    public function testDescriptionHasCircuitBreaker()
    {
        $client = new Client($this->description);

        $this->assertTrue($client->getService()->wantsCircuitBreaker());
        $this->assertTrue($client->getCircuitBreaker() instanceof CircuitBreaker);
    }

    public function testFailureCausesException()
    {
        $client = new Client($this->description);

        $this->setExpectedException(ServiceUnavailableException::class);

        $client->failure();
    }

    public function test400WithValidErrorPayloadDoesNotCauseCircuitBreakerFailure()
    {
        // We're going to call an endpoint that results in a 400 failure,
        // however it contains a valid JSON error payload. We do NOT want this
        // to result in a circuit breaker failure. Instead we expect a generic
        // ServiceResponseException

        $client = new Client($this->description);

        $this->setExpectedException(ServiceResponseException::class);

        $client->remoteErrorWithDefaultException();
    }

    public function test400WithInvalidErrorPayloadCausesCircuitBreakerFailure()
    {
        // A 4XX response with a valid error payload should not trigger circuit breaker,
        // however a 4XX _without_ a valid error payload should:

        $client = new Client($this->description);

        $this->setExpectedException(ServiceErrorException::class);

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
        usleep(100000);
        $this->assertTrue($client->getCircuitBreaker()->isAvailable());

        // And now just one more failure
        $this->callFailureAndSuppress($client);

        $this->assertFalse($client->isAvailable());

        // Since we're unavailable, we will now get a CircuitBreakerOpenException
        $this->setExpectedException(CircuitBreakerOpenException::class);
        $client->success();
    }

    public function testLogging()
    {
        // Add in our logger class
        $description = $this->description;
        $description['logger'] = MyLogger::class;

        $client = new Client($description);

        $GLOBALS['loglevel'] = '';
        $this->callFailureAndSuppress($client);

        $this->assertEquals("error", $GLOBALS['loglevel']);
    }

    protected function callFailureAndSuppress($client)
    {
        try {
            $client->failure();
        } catch (ServiceUnavailableException $e) {
        }
    }
}


class MyLogger implements LoggerInterface
{
    public function emergency($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'emergency';
    }
    public function alert($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'alert';
    }
    public function critical($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'critical';
    }
    public function error($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'error';
    }
    public function warning($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'warning';
    }
    public function notice($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'notice';
    }
    public function info($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'info';
    }
    public function debug($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'debug';
    }
    public function log($level, $message, array $context = array()) {
        return $this->{$level}($message, $context);
    }
}
