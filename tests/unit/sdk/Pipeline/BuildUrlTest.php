<?php
namespace Sdk\Pipeline;

use Illuminate\Container\Container;
use RC\Sdk\HttpClient;
use RC\Sdk\Pipeline\BuildUrl;
use RC\Sdk\Request;

class BuildUrlTest extends \PHPUnit_Framework_TestCase
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

        $requestDTO = new Request($client, $baseUrl, $config, $arguments);
        $buildUrl = new BuildUrl();
        $request = $buildUrl->handle($requestDTO, function($request){return $request;});
        $this->assertEquals('http://php.unit/test/oazwsdob', $request->url);
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

        $requestDTO = new Request($client, $baseUrl, $config, $arguments);
        $buildUrl = new BuildUrl();
        $request = $buildUrl->handle($requestDTO, function($request){return $request;});
        $this->assertEquals('http://php.unit/test/oazwsdob', $request->url);
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

        $requestDTO = new Request($client, $baseUrl, $config, $arguments);
        $buildUrl = new BuildUrl();
        $this->setExpectedException('InvalidArgumentException');
        $buildUrl->handle($requestDTO, function($request){return $request;});
    }

}