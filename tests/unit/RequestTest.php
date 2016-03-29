<?php
namespace STS\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use STS\Sdk\Service;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    protected $description = [
        'name' => 'Test',
        'baseUrl' => 'http://mockbin.org/bin',
        'options' => [
           "foo" => "bar",
            "baz" => "quz"
        ],
        'operations' => [
            'getOk' => [
                'httpMethod' => 'GET',
                'uri' => '/f738e274-ba99-4405-accd-5bfb0358f27b',
                'options' => [
                    "baz" => 123
                ]
            ]
        ]
    ];

    /**
     * Just test our basic getters/settings. More advanced stuff gets hit in the functional tests.
     */
    public function testBasics()
    {
        $guzzle = new Client(['base_uri' => 'http://foo.local']);
        $description = new Service($this->description);

        $request = new Request($guzzle, "MySdk", $description, $description->getOperation('getOk'), []);

        $this->assertEquals($request->getServiceName(), 'MySdk');

        $this->assertEquals($request->getDescription()->getBaseUrl(), 'http://mockbin.org/bin');

        // This passes through our __call method
        $this->assertTrue($request->getUri() instanceof Uri);

        $this->assertEquals($request->getOperation()->getName(), 'getOk');

        $this->assertEquals(10, $request->getRequestOptions()['timeout']);  // Request defaults
        $this->assertEquals("bar", $request->getRequestOptions()['foo']);  // Description options
        $this->assertEquals(123, $request->getRequestOptions()['baz']);  // Operation options, overriding description
    }
}
