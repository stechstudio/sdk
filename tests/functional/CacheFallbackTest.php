<?php
namespace STS\Sdk;

use GuzzleHttp\Exception\ClientException;
use Psr\Log\LoggerInterface;
use Stash\Pool;
use STS\Sdk\Exceptions\CircuitBreakerOpenException;
use STS\Sdk\Exceptions\ServiceResponseException;
use STS\Sdk\Exceptions\ServiceUnavailableException;
use STS\Sdk\Request\Cache;
use STS\Sdk\Service\Description;
use Mockery AS m;
use STS\Sdk\Service\Operation;

class CacheFallbackTest extends \PHPUnit_Framework_TestCase
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
            ],
            'remoteErrorWithDefaultException' => [
                'httpMethod' => 'GET',
                'uri' => '/bin/e59a596a-5965-4e00-a1f8-f50474ddd9d3'
            ],
        ]
    ];

    public function testCacheFallbackUsedWhenServiceUnavailable()
    {
        $workingOperation = new Operation("Foo", [
            'httpMethod' => 'GET',
            'uri' => '/bin/f738e274-ba99-4405-accd-5bfb0358f27b'
        ]);

        $failingOperationThatMatches = new Operation("Foo", [
            'httpMethod' => 'GET',
            'uri' => '/bin/3d5b8a1a-48a3-47e9-8fe8-88a3887d99ef'
        ]);

        $failingOperationWithDifferentName = new Operation("Bar", [
            'httpMethod' => 'GET',
            'uri' => '/bin/3d5b8a1a-48a3-47e9-8fe8-88a3887d99ef'
        ]);

        $description = m::mock(Description::class, [$this->description])->makePartial();
        $description->shouldReceive("getOperation")->times(5)->andReturn(
            $workingOperation,                  // First call, we want this to work
            $workingOperation,                  // When we prepare the same request again to figure out the cache key
            $failingOperationThatMatches,       // Second call, want this to fail and fallback to cache
            $workingOperation,                  // Third call, we'll trip the circuit breaker and make sure it falls back
            $failingOperationWithDifferentName  // Fourth call to a failing endpoing with a different operation name, no cache available, ServiceUnavailableExeption
        );

        $client = new Client($description);
        $cache = new Cache(new Pool());

        // This just works
        $this->assertEquals("ok", $client->success());

        // Let's manually get the request so we can find out our cache name
        // Note the name doesn't matter, our mock is going to return the second return queue value above
        $request = $client->prepareRequest("x", []);
        $cache->store($request, "ok from cache");

        // This fails, pulls from cache
        $this->assertEquals("ok from cache", $client->success());

        // This one is the working endpoint, but we're tripping the breaker first
        $client->getCircuitBreaker()->trip();
        $cache->store($request, "ok from cache again");
        $this->assertEquals("ok from cache again", $client->success());

        // This is a different operation name, so it won't be in cache
        $client->getCircuitBreaker()->reset();
        $this->setExpectedException(ServiceUnavailableException::class);
        $client->success();
    }
}