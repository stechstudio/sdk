<?php
namespace Sdk\Pipeline;

use RC\Sdk\Pipeline\BuildUri;
use RC\Sdk\Operation;
use RC\Sdk\Description;
use RC\Sdk\Request;
use Mockery as m;

class BuildUriTest extends \TestCase
{
    /**
     * Build a uri that includes the baseUrl, and a query key/value pair
     */
    public function testPartialUri()
    {
        $description = m::mock(Description::class);
        $description->shouldReceive('getBaseUrl')->andReturn("http://www.foo.local");

        $operation = m::mock(Operation::class);
        $operation->shouldReceive("getDataByLocation")->with("uri")->andReturn(["foo" => "bar"]);
        $operation->shouldReceive("getDataByLocation")->with("query")->andReturn(["baz" => "qux"]);
        $operation->shouldReceive('getUri')->andReturn('/uri');

        $request = m::mock(Request::class);
        $request->shouldReceive("getOperation")->andReturn($operation);
        $request->shouldReceive("getDescription")->andReturn($description);

        $request->shouldReceive('setUri')->with("http://www.foo.local/uri?baz=qux");

        $buildUri = new BuildUri();
        $result = $buildUri->handle($request, function() { return "result"; });

        $this->assertEquals($result, "result");
    }

    /**
     * Include a variable in the uri
     */
    public function testPartialUriWithVariable()
    {
        $description = m::mock(Description::class);
        $description->shouldReceive('getBaseUrl')->andReturn("http://www.foo.local");

        $operation = m::mock(Operation::class);
        $operation->shouldReceive("getDataByLocation")->with("uri")->andReturn(["id" => 5]);
        $operation->shouldReceive("getDataByLocation")->with("query")->andReturn(["baz" => "qux"]);
        $operation->shouldReceive('getUri')->andReturn('/uri/{id}');

        $request = m::mock(Request::class);
        $request->shouldReceive("getOperation")->andReturn($operation);
        $request->shouldReceive("getDescription")->andReturn($description);

        $request->shouldReceive('setUri')->with("http://www.foo.local/uri/5?baz=qux");

        $buildUri = new BuildUri();
        $result = $buildUri->handle($request, function() { return "result"; });

        $this->assertEquals($result, "result");
    }

    /**
     * Build a uri that is provided entirely from the operation, and does not include the baseUrl
     */
    public function testFullUri()
    {
        $description = m::mock(Description::class);
        $description->shouldReceive('getBaseUrl')->andReturn("http://www.foo.local");

        $operation = m::mock(Operation::class);
        $operation->shouldReceive("getDataByLocation")->with("uri")->andReturn(["id" => 5]);
        $operation->shouldReceive("getDataByLocation")->with("query")->andReturn(["baz" => "qux"]);
        $operation->shouldReceive('getUri')->andReturn('http://www.bar.local/uri');

        $request = m::mock(Request::class);
        $request->shouldReceive("getOperation")->andReturn($operation);
        $request->shouldReceive("getDescription")->andReturn($description);

        $request->shouldReceive('setUri')->with("http://www.bar.local/uri?baz=qux");

        $buildUri = new BuildUri();
        $result = $buildUri->handle($request, function() { return "result"; });

        $this->assertEquals($result, "result");
    }

}