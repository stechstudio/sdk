<?php
use RC\Sdk\Request;

/**
 * Created by PhpStorm.
 * User: Bubba
 * Date: 1/14/2016
 * Time: 11:50 AM
 */
class RequestTest extends PHPUnit_Framework_TestCase
{
    public function testInstantiation()
    {
        $client = new \RC\Sdk\HttpClient(new \Illuminate\Container\Container());
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
                    ],
                    "foo" => [
                        "location" => "query"
                    ]
                ]
            ];
        $arguments = ['foz', 'baz', 'sheesh'];

        $requestDTO = new Request($client,  'name', 'flartybart', $baseUrl, $config, $arguments);
        $this->assertObjectHasAttribute('client', $requestDTO, ' Should have a client attribute');
        $this->assertObjectHasAttribute('baseUrl', $requestDTO, 'Should have a baseURL attribute');
        $this->assertObjectHasAttribute('config', $requestDTO, 'Should have a config attribute');
        $this->assertObjectHasAttribute('arguments', $requestDTO, 'Should have an arguments attribute');
//        $this->assertObjectHasAttribute('body', $requestDTO, 'Should have a body attribute');
//        $this->assertObjectHasAttribute('headers', $requestDTO, 'Should have a headers attribute');
//        $this->assertObjectHasAttribute('url', $requestDTO, 'Should have a url attribute');
        $this->assertObjectHasAttribute('response', $requestDTO, 'Should have a response attribute');
        $this->assertObjectHasAttribute('responseBody', $requestDTO, 'Should have a responseBody attribute');
    }

    public function testNoHttpMethod()
    {
        $client = new \RC\Sdk\HttpClient(new \Illuminate\Container\Container());
        $baseUrl = 'http://php.unit/test';
        $config = [
            "uri" => "/oazwsdob",
            "parameters" => [
                "domain" => [
                    "validate" => "required|string",
                    "location" => "body"
                ],
                "id" => [
                    "validate" => "required|numeric",
                    "location" => "uri"
                ],
                "foo" => [
                    "location" => "query"
                ]
            ]
        ];
        $arguments = ['foz', 'baz', 'sheesh'];
        $this->setExpectedException('InvalidArgumentException');
        new Request($client, 'name', 'flartybart', $baseUrl, $config, $arguments);
    }

    public function testNoLocation()
    {
        $client = new \RC\Sdk\HttpClient(new \Illuminate\Container\Container());
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
                ],
                "foo" => [
                    "validate" => "required|string"
                ]
            ]
        ];
        $arguments = ['domain' => 'php.unit', 'id'=>1, 'foo'=>'bar'];
        $req = new Request($client, 'name', 'flartybart', $baseUrl, $config, $arguments);
        $this->assertEquals($req->arguments, $req->getArguments(null));
    }

}
