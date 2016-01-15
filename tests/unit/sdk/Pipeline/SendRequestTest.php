<?php
namespace Sdk\Pipeline;

use Illuminate\Container\Container;
use RC\Sdk\HttpClient;
use RC\Sdk\Pipeline\SendRequest;
use RC\Sdk\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class SendRequestTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiation()
    {
        $testBody = ["some" =>"test"];
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], json_encode($testBody)),
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);


        $httpClient = new HttpClient(new Container());
        $httpClient->setGuzzle($client);
        $baseUrl = 'http://php.unit/test';
        $config = [
            "httpMethod" => "POST",
            "uri" => "/oazwsdob",
            "parameters" => [
                "domain" => [
                    "validate" => "required|string",
                    "location" => "body"
                ],
                "id" => [
                    "validate" => "required|numeric",
                    "location" => "uri"
                ]
            ]
        ];
        $arguments = ['foz', 'baz', 'sheesh'];

        $requestDTO = new Request($httpClient, 'name', 'flartybart', $baseUrl, $config, $arguments);
        $requestDTO->url = 'http://php.unit/test/oazwsdob';
        $sendRequests = new SendRequest();
        $request = $sendRequests->handle($requestDTO, function($request){return $request;});
        $this->assertEquals($testBody, $request->responseBody);
    }
}