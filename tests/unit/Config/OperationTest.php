<?php
namespace RC\Sdk\Config;

use PHPUnit_Framework_TestCase;
use InvalidArgumentException;

class OperationTest extends PHPUnit_Framework_TestCase
{
    public function testInstantiate()
    {
        $o = new Operation("foo", [], []);
        $this->assertTrue($o instanceof Operation);
    }

    public function testSimpleGetters()
    {
        $o = new Operation("foo", [
            'httpMethod' => 'POST',
            'uri' => '/bar',
            'parameters' => [
                'baz' => [
                    'location' => 'json'
                ]
            ],
            'additionalParameters' => [],
        ], []);

        $this->assertEquals($o->getName(), "foo");
        $this->assertEquals($o->getHttpMethod(), "POST");
        $this->assertEquals($o->getUri(), "/bar");
        $this->assertEquals(count($o->getParameters()), 1);
    }

    public function testParameters()
    {
        $o = new Operation("foo", [
            'httpMethod' => 'POST',
            'uri' => '/bar',
            'parameters' => [
                'baz' => [
                    'location' => 'json',
                    'validate' => 'required|string'
                ],
                'quz' => [
                    'location' => 'body'
                ],
                'corge' => [
                    'location' => 'json',
                    'validate' => 'numeric'
                ],
                'grault' => [
                    'location' => 'uri'
                ]
            ],
            'additionalParameters' => [],
        ], []);

        $this->assertEquals(count($o->getParameters()), 4);
        $this->assertEquals(count($o->getParametersByLocation('json')), 2);
        $this->assertEquals(count($o->getParametersByLocation('uri')), 1);

        $this->assertEquals(count($o->getValidationRules()), 2);
        $this->assertEquals($o->getValidationRules()['baz'], 'required|string');
    }

    public function testAdditionalParameters()
    {
        $o = new Operation("foo", [
            'httpMethod' => 'POST',
            'uri' => '/bar',
            'parameters' => [
                'baz' => [
                    'location' => 'json',
                    'validate' => 'required|string'
                ],
                'quz' => [
                    'location' => 'body'
                ],
            ],
            'additionalParameters' => [
                'location' => 'json'
            ],
        ], []);

        $this->assertTrue($o->allowAdditionalParametersAt("json"));
        $this->assertFalse($o->allowAdditionalParametersAt("query"));
    }

    public function testData()
    {
        $o = new Operation("foo", [
            'httpMethod' => 'POST',
            'uri' => '/bar',
            'parameters' => [
                'baz' => [
                    'location' => 'json',
                    'validate' => 'required|string'
                ],
                'quz' => [
                    'location' => 'body'
                ],
                'corge' => [
                    'location' => 'json',
                    'validate' => 'numeric'
                ],
                'grault' => [
                    'location' => 'uri',
                    'default' => 'grault-default-value'
                ]
            ],
            'additionalParameters' => null,
        ], [
            'baz' => 'hello',
            'corge' => 'string',
            'extra' => 'should be excluded'
        ]);

        $this->assertEquals(count($o->getData()), 3);
        $this->assertFalse(array_key_exists('extra', $o->getData()));

        $this->assertEquals(count($o->getDataByLocation('json')), 2);
        $this->assertEquals(count($o->getDataByLocation('uri')), 1);
    }

    public function testDataWithAdditionalParameters()
    {
        $o = new Operation("foo", [
            'httpMethod' => 'POST',
            'uri' => '/bar',
            'parameters' => [
                'baz' => [
                    'location' => 'json',
                    'validate' => 'required|string'
                ],
                'quz' => [
                    'location' => 'body'
                ],
                'corge' => [
                    'location' => 'json',
                    'validate' => 'numeric'
                ],
                'grault' => [
                    'location' => 'uri',
                    'default' => 'grault-default-value'
                ]
            ],
            'additionalParameters' => [
                'location' => 'json'
            ],
        ], [
            'baz' => 'hello',
            'corge' => 'string',
            'extra' => 'should be included'
        ]);

        $this->assertEquals(count($o->getData()), 4);
        $this->assertTrue(array_key_exists('extra', $o->getData()));

        $this->assertEquals(count($o->getDataByLocation('json')), 3); // Includes the extra data
        $this->assertEquals(count($o->getDataByLocation('uri')), 1); // Does NOT include extra data
    }

    public function testInvalidParameter()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $o = new Operation('foo', [
            'parameters' => [
                'invalid' => false
            ]
        ], []);
    }
}


