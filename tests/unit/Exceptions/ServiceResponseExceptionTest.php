<?php
namespace STS\Sdk\Exceptions;

class ServiceResponseExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testException()
    {
        $exception = new ServiceResponseException("Error message", 111);

        $this->assertEquals($exception->getErrorCode(), 111);
        $this->assertEquals($exception->getStatus(), 400);
        $this->assertEquals($exception->getHttpStatusCode(), 400);
        $this->assertEquals($exception->getErrorName(), 'ServiceResponseException');
    }
}