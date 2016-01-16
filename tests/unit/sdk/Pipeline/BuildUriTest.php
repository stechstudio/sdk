<?php
namespace Sdk\Pipeline;

use Illuminate\Container\Container;
use RC\Sdk\HttpClient;
use RC\Sdk\Pipeline\BuildUri;
use RC\Sdk\Request;

class BuildUriTest extends \PHPUnit_Framework_TestCase
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
        $arguments = ["id"=>1, "domain"=>"php.unit"];

        $requestDTO = new Request($client, 'name', 'flartybart',$baseUrl, $config, $arguments);
        $buildUrl = new BuildUri();
        $request = $buildUrl->handle($requestDTO, function($request){return $request;});
        $this->assertEquals('http://php.unit/test/oazwsdob', (string)$request->getUri());
    }

    public function testFullUri()
    {
        $client = new HttpClient(new Container());
        $baseUrl = 'http://php.unit/test';
        $config = [
            "httpMethod" => "POST",
            "uri" => "http://php.unit/test/oazwsdob",
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
        $arguments = ["id"=>1, "domain"=>"php.unit"];

        $requestDTO = new Request($client, 'name', 'flartybart',$baseUrl, $config, $arguments);
        $buildUrl = new BuildUri();
        $request = $buildUrl->handle($requestDTO, function($request){return $request;});
        $this->assertEquals('http://php.unit/test/oazwsdob', (string)$request->getUri());
    }

    public function testMissingUri()
    {
        $client = new HttpClient(new Container());
        $baseUrl = 'http://php.unit/test';
        $config = [
            "httpMethod" => "POST",
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
        $arguments = ["id"=>1, "domain"=>"php.unit"];


        $this->setExpectedException('InvalidArgumentException');
        $requestDTO = new Request($client, 'name', 'flartybart', $baseUrl, $config, $arguments);
    }

}