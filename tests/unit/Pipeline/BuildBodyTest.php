<?php
namespace STS\Sdk\Pipeline;

use STS\Sdk\Request;
use Mockery as m;
use STS\Sdk\Service\Operation;

class BuildBodyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * When one or more JSON parameters are provided, we expect to only have a json body and the header set
     */
    public function testJsonBody()
    {
        $operation = m::mock(Operation::class);
        $operation->shouldReceive("getDataByLocation")->with("json")->andReturn(["foo" => "bar"]);
        $operation->shouldReceive("getDataByLocation")->with("body")->andReturn(["baz" => "qux"]);

        $request = m::mock(Request::class);
        $request->shouldReceive("getOperation")->andReturn($operation);

        $request->shouldReceive("setBody")->with('{"foo":"bar"}')->once();
        $request->shouldReceive("setHeader")->withArgs(["Content-Type","application/json"])->once();

        $buildBody = new BuildBody();
        $result = $buildBody->handle($request, function() { return "result"; });

        $this->assertEquals($result, "result");
    }

    /**
     * With no JSON parameters, the raw body param value will be our only body
     */
    public function testRawBody()
    {
        $operation = m::mock(Operation::class);
        $operation->shouldReceive("getDataByLocation")->with("json")->andReturn([]);
        $operation->shouldReceive("getDataByLocation")->with("body")->andReturn(["baz" => "qux"]);

        $request = m::mock(Request::class);
        $request->shouldReceive("getOperation")->andReturn($operation);

        $request->shouldReceive("setBody")->with('qux')->once();
        $request->shouldNotReceive("setHeader");

        $buildBody = new BuildBody();
        $result = $buildBody->handle($request, function() { return "result"; });

        $this->assertEquals($result, "result");
    }
}