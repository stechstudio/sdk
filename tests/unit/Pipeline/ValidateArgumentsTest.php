<?php
namespace Sdk\Pipeline;

use Illuminate\Validation\Validator;
use STS\Sdk\Pipeline\ValidateArguments;
use STS\Sdk\Request;
use Mockery as m;
use STS\Sdk\Exceptions\ValidationException;

class ValidateArgumentsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Passing validation
     */
    public function testValid()
    {
        $operation = m::mock(Operation::class);
        $operation->shouldReceive("getValidationRules")->once()->andReturn(["foo" => "required|string"]);
        $operation->shouldReceive("getData")->once()->andReturn(["foo" => "bar"]);

        $request = m::mock(Request::class);
        $request->shouldReceive("getOperation")->twice()->andReturn($operation);

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
        $operation->shouldReceive("getValidationRules")->andReturn(["foo" => "required|numeric", "bar" => "required"]);
        $operation->shouldReceive("getData")->andReturn(["foo" => "abc"]);

        $request = m::mock(Request::class);
        $request->shouldReceive("getOperation")->andReturn($operation);

        $validateArguments = new ValidateArguments();

        try {
            $validateArguments->handle($request, function () {
                return "result";
            });
        } catch(ValidationException $e) {}

        $this->assertTrue($e->getValidator() instanceof Validator);
    }
}
