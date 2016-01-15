<?php
namespace Sdk\Pipeline;

use Illuminate\Container\Container;
use RC\Sdk\HttpClient;
use RC\Sdk\Pipeline\BuildBody;
use RC\Sdk\Request;

class BuildBodyTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiation()
    {
        $client = new HttpClient(new Container());
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

        $requestDTO = new Request($client, 'flartybart', $baseUrl, $config, $arguments);
        $buildBody = new BuildBody();

        $request = $buildBody->handle($requestDTO, function($request){return $request;});
        $this->assertEquals('[]', $request->body);
    }
}