<?php
namespace Sdk\Pipeline;

use RC\Sdk\Pipeline\SendRequest;
use RC\Sdk\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Mockery as m;

class SendRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * JSON response body should be decoded
     */
    public function testJSONResult()
    {
        $response = m::mock(Response::class);
        $response->shouldReceive('getBody')->andReturn('{"foo":"bar"}');

        $request = m::mock(Request::class);
        $request->shouldReceive('send')->andReturn($response)->once();
        $request->shouldReceive('saveResponse')->with(m::type('object'), ["foo" => "bar"])->once();

        $sendRequest = new SendRequest();
        $result = $sendRequest->handle($request, function() { return "result"; });

        $this->assertEquals($result, "result");
    }

    /**
     * Non-JSON response body
     */
    public function testNonJSONResult()
    {
        $response = m::mock(Response::class);
        $response->shouldReceive('getBody')->andReturn('foobar');

        $request = m::mock(Request::class);
        $request->shouldReceive('send')->andReturn($response)->once();
        $request->shouldReceive('saveResponse')->with(m::type('object'), "foobar")->once();

        $sendRequest = new SendRequest();
        $result = $sendRequest->handle($request, function() { return "result"; });

        $this->assertEquals($result, "result");
    }
}