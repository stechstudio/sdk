<?php
/**
 * Created by PhpStorm.
 * User: Bubba
 * Date: 1/14/2016
 * Time: 12:21 PM
 */

namespace Sdk\Pipeline;
use RC\Sdk\Request;
use Illuminate\Container\Container;
use RC\Sdk\HttpClient;
use RC\Sdk\Pipeline\AddCorrelationID;

class CorrelationIDTest extends \PHPUnit_Framework_TestCase
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
        $correlationID = new AddCorrelationID();

        $request = $correlationID->handle($requestDTO, function($request){return $request;});
        $this->assertNotEmpty($request->headers['X-Correlation-ID']);
    }
}
