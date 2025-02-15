<?php
namespace Tests\Service;

use STS\Sdk\Service\Parameter;
use Tests\TestCase;

class ParameterTest extends TestCase
{
    public function testInstantiate()
    {
        $p = new Parameter("foo", "bar", []);
        $this->assertTrue($p instanceof Parameter);
    }

    public function testSimpleGetters()
    {
        $p = new Parameter('foo', 'bar', [
            'validate' => 'required',
            'location' => 'json'
        ]);

        $this->assertEquals($p->getName(), 'foo');
        $this->assertEquals($p->getValue(), 'bar');
        $this->assertEquals($p->getValidate(), 'required');
        $this->assertEquals($p->getLocation(), 'json');
    }

    public function testDefaultValue()
    {
        $p = new Parameter('foo', null, [
            'default' => 'baz'
        ]);

        $this->assertEquals($p->getValue(), 'baz');

        $p = new Parameter('foo', 'bar', [
            'default' => 'baz'
        ]);

        $this->assertEquals($p->getValue(), 'bar');
        $this->assertEquals($p->getDefault(), 'baz');
    }

    public function testSentAs()
    {
        $p = new Parameter('myFoo', null, []);

        $this->assertEquals($p->getName(), "myFoo");

        $p = new Parameter('myFoo', null, [
            "sentAs" => "my-foo"
        ]);

        $this->assertEquals($p->getName(), "my-foo");
    }

    public function testSetValue()
    {
        $p = new Parameter('myFoo', "value", []);
        $this->assertEquals($p->getValue(), 'value');

        $p->setValue("newvalue");
        $this->assertEquals($p->getValue(), 'newvalue');
    }
}
