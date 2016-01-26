<?php
namespace STS\Sdk;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;
use STS\Sdk\Service\Description;

class RequestTest extends \PHPUnit_Framework_TestCase
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

    /**
     * Just test our basic getters/settings. More advanced stuff gets hit in the functional tests.
     */
    public function testBasics()
    {
        $guzzle = new Client(['base_uri' => 'http://foo.local']);
        $description = new Description($this->description);

        $request = new Request($guzzle, "MySdk", $description, $description->getOperation('getOk'), []);

        $this->assertEquals($request->getServiceName(), 'MySdk');

        $this->assertEquals($request->getDescription()->getBaseUrl(), 'http://mockbin.org/bin');

        // This passes through our __call method
        $this->assertTrue($request->getUri() instanceof Uri);

        $this->assertEquals($request->getOperation()->getName(), 'getOk');
    }
}