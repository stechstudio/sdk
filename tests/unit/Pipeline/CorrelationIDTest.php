<?php
/**
 * Created by PhpStorm.
 * User: Bubba
 * Date: 1/14/2016
 * Time: 12:21 PM
 */

namespace Sdk\Pipeline;
use STS\Sdk\Pipeline\AddCorrelationID;
use STS\Sdk\Request;
use Mockery as m;

class CorrelationIDTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Just make sure the header is set, we don't care what the value is
     */
    public function testGeneratedCorrelationID()
    {
        $request = m::mock(Request::class);
        $request->shouldReceive('setHeader')->once();

        $correlationID = new AddCorrelationID();
        $result = $correlationID->handle($request, function() { return "result"; });

        $this->assertEquals($result, "result");
    }

    /**
     * This time our header should match the environment variable
     */
    public function testSpecificCorrelationID()
    {
        putenv('CORRELATION_ID=1234567');

        $request = m::mock(Request::class);
        $request->shouldReceive('setHeader')->with("X-Correlation-ID", "1234567")->once();

        $correlationID = new AddCorrelationID();
        $result = $correlationID->handle($request, function() { return "result"; });

        $this->assertEquals($result, "result");
    }
}
