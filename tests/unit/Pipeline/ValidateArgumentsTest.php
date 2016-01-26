<?php
namespace STS\Sdk\Pipeline;

use STS\Sdk\Request;
use Mockery as m;
use Illuminate\Validation\ValidationException;

class ValidateArgumentsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Passing validation
     */
    public function testValid()
    {
        $operation = m::mock(Operation::class);
        $operation->shouldReceive("getValidationRules")->andReturn(["foo" => "required|string"]);
        $operation->shouldReceive("getData")->andReturn(["foo" => "bar"]);

        $request = m::mock(Request::class);
        $request->shouldReceive("getOperation")->andReturn($operation);

        $validateArguments = new ValidateArguments();
        $result = $validateArguments->handle($request, function() { return "result"; });

        $this->assertEquals($result, "result");
    }

    /**
     * Failing validation
     */
    public function testInvalid()
    {
        $operation = m::mock(Operation::class);
        $operation->shouldReceive("getValidationRules")->andReturn(["foo" => "required|numeric"]);
        $operation->shouldReceive("getData")->andReturn(["foo" => "bar"]);

        $request = m::mock(Request::class);
        $request->shouldReceive("getOperation")->andReturn($operation);

        $this->setExpectedException(ValidationException::class);

        $validateArguments = new ValidateArguments();
        $validateArguments->handle($request, function() { return "result"; });
    }
}