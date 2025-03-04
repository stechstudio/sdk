<?php
namespace Tests\Service;

use InvalidArgumentException;
use STS\Sdk\Service\Operation;
use Tests\TestCase;

class OperationTest extends TestCase
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

        $this->assertEquals('baz', $o->getParameter('baz')->getName());

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

        // Verify that a true parameter object was setup for our additional data
        $this->assertTrue(array_key_exists("extra", $o->getParameters()));
        $this->assertEquals('json', $o->getParameter('extra')->getLocation());
    }

    public function testInvalidParameter()
    {
        $this->expectException(InvalidArgumentException::class);

        $o = new Operation('foo', [
            'parameters' => [
                'invalid' => false
            ]
        ], []);
    }

    public function testValidationRulesWithSentAs()
    {
        $o = new Operation("foo", [
            'httpMethod' => 'POST',
            'uri' => '/bar',
            'parameters' => [
                'foo' => [
                    'location' => 'json',
                    'sentAs' => 'bar',
                    'validate' => 'required'
                ],
                'abc' => [
                    'location' => 'json'
                ]
            ],
            'additionalParameters' => [],
        ], ['foo' => 123, 'abc' => 456]);

        // Note we want to ensure that the 'foo' key is now listed as 'bar' so the
        // validation rules keys match the modified data keys
        $this->assertTrue(array_key_exists('bar', $o->getValidationRules()));
        $this->assertTrue(array_key_exists('bar', $o->getData()));
    }

    public function testCache()
    {
        $o = new Operation("foo", [
            'httpMethod' => 'POST'
        ], []);

        $this->assertFalse($o->wantsCache());
        $this->assertFalse($o->prefersCache());

        $o = new Operation("foo", [
            'httpMethod' => 'GET'
        ], []);

        $this->assertTrue($o->wantsCache());
        $this->assertFalse($o->prefersCache());

        $o = new Operation("foo", [
            'httpMethod' => 'GET',
            'cache' => [
                'fallback' => false
            ]
        ], []);

        $this->assertFalse($o->wantsCache());
        $this->assertFalse($o->prefersCache());

        $o = new Operation("foo", [
            'httpMethod' => 'POST',
            'cache' => [
                'fallback' => true
            ]
        ], []);

        $this->assertTrue($o->wantsCache());
        $this->assertFalse($o->prefersCache());

        $o = new Operation("foo", [
            'httpMethod' => 'GET',
            'cache' => [
                'prefers' => true
            ]
        ], []);

        $this->assertTrue($o->prefersCache());
    }

    public function testOptions()
    {
        $o = new Operation("foo", [
            'httpMethod' => 'GET',
            'options' => [
                "foo" => "bar"
            ]
        ], []);

        $this->assertEquals("bar", $o->getOptions()['foo']);
    }

    public function testResponseModel()
    {
        $o = new Operation("foo", [
            'httpMethod' => 'GET',
            'response' => [
                "model" => "ResponseModelClass"
            ]
        ], []);

        $this->assertTrue($o->hasResponseModelClass());
        $this->assertEquals("ResponseModelClass", $o->getResponseModelClass());
        $this->assertFalse($o->wantsResponseCollection());

        $o = new Operation("foo", [
            'httpMethod' => 'GET',
            'response' => [
                "model" => "ResponseModelClass",
                "collection" => true
            ]
        ], []);

        $this->assertTrue($o->wantsResponseCollection());

        $o = new Operation("foo", [
            'httpMethod' => 'GET',
        ], []);

        $this->assertFalse($o->hasResponseModelClass());
    }
}
