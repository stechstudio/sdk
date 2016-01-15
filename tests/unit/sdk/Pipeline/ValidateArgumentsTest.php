<?php
namespace Sdk\Pipeline;

use Illuminate\Container\Container;
use RC\Sdk\HttpClient;
use RC\Sdk\Pipeline\BuildBody;
use RC\Sdk\Pipeline\ValidateArguments;
use RC\Sdk\Request;

class ValidateArgumentsTest extends \PHPUnit_Framework_TestCase
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
                ],
                "noise" => [
                    "location" => "n/a"
                ]
            ]
        ];
        $arguments = ["id"=>1, "domain"=>"php.unit"];

        $requestDTO = new Request($client, 'flartybart', $baseUrl, $config, $arguments);
        $validateArg = new ValidateArguments();
        $request = $validateArg->handle($requestDTO, function($request){return $request;});
        $this->assertEquals(Request::class, get_class($request), 'We should get a valided request object back.');
    }

    public function testValidation()
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
                ],
                "noise" => [
                    "location" => "n/a"
                ]
            ]
        ];
        $arguments = ["id"=> "a", "domain"=>"php.unit"];

        $requestDTO = new Request($client, 'flartybart', $baseUrl, $config, $arguments);
        $validateArg = new ValidateArguments();
        $this->setExpectedException('Illuminate\Validation\ValidationException');
        $validateArg->handle($requestDTO, function($request){return $request;});

    }
}