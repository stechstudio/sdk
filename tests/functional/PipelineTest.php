<?php
namespace STS\Sdk;

use Closure;
use STS\Sdk\Request;

class PipelineTest extends \PHPUnit_Framework_TestCase
{
    protected $description = [
        'baseUrl' => 'http://mockbin.org',
        'operations' => [
            'getOk' => [
                'httpMethod' => 'GET',
                'uri' => '/bin/f738e274-ba99-4405-accd-5bfb0358f27b'
            ],
            'getRequest' => [
                'httpMethod' => 'GET',
                'uri' => '/request'
            ],
            'getRequestWithBody' => [
                'httpMethod' => 'GET',
                'uri' => '/request',
                'parameters' => [
                    'foo' => [
                        'location' => 'json'
                    ]
                ]
            ]
        ]
    ];

    /**
     * Verify we can add a pipe at all
     */
    public function testCustomPipe()
    {
        $client = new Client($this->description);
        $client->appendPipe(BreakingPipe::class);

        $result = $client->getOk();

        // Confirms that the pipe was successfully added, since it breaks the pipeline and returns a string
        $this->assertEquals($result, "BREAKING PIPE!");
    }

    /**
     * Verify we can add a pipe to set a header
     */
    public function testHeaderPipe()
    {
        $client = new Client($this->description);
        $client->appendPipe(HeaderPipe::class);

        $result = $client->getRequest();
        $this->assertTrue(isset($result['headers']['x-foo']));
        $this->assertEquals($result['headers']['x-foo'], 'BAR');
    }

    /**
     * Verify our body is being set correctly
     */
    public function testBody()
    {
        $client = new Client($this->description);
        $result = $client->getRequestWithBody(["foo" => "bar"]);

        $this->assertEquals($result['bodySize'], 13);
    }

    /**
     * Verify we can access the guzzle request object from inside the pipeline
     */
    public function testGetRequestPath()
    {
        $client = new Client($this->description);
        $client->appendPipe(GetPathPipe::class);
        $result = $client->getRequest();

        $this->assertEquals($result, "/request");
    }
}

class BreakingPipe {
    public function handle(Request $request, Closure $next)
    {
        return "BREAKING PIPE!";
    }
}

class HeaderPipe {
    public function handle(Request $request, Closure $next)
    {
        $request->setHeader('X-Foo', "BAR");

        return $next($request);
    }
}

class GetPathPipe {
    public function handle(Request $request, Closure $next)
    {
        return $request->getUri()->getPath();
    }
}