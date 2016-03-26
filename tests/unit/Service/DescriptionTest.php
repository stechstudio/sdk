<?php
namespace STS\Sdk\Service;

use PHPUnit_Framework_TestCase;
use InvalidArgumentException;
use Stash\Pool;
use STS\Sdk\CircuitBreaker;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class DescriptionTest extends PHPUnit_Framework_TestCase
{
    public function testInstantiate()
    {
        $d = new Description([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => []
        ]);
        $this->assertTrue($d instanceof Description);
    }

    public function testEmptyConfig()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $d = new Description([]);
    }

    public function testBadOperationsConfig()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $d = new Description(['name' => 'test', 'baseUrl' => 'foo', 'operations' => false]);
    }

    public function testTopLevelParameters()
    {
        $d = new Description([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => []
        ]);

        $this->assertEquals('http://www.foo.local',$d->getBaseUrl());
        $this->assertEquals('test',$d->getName());
    }

    public function testGetOperation()
    {
        $d = new Description([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => [
                'foo' => [
                    'httpMethod' => 'POST',
                    'uri' => '/bar',
                    'parameters' => [
                        'baz' => [
                            'location' => 'json'
                        ]
                    ],
                    'additionalParameters' => [],
                ]
            ]
        ]);

        $this->assertTrue($d->hasOperation("foo"));
        $this->assertTrue($d->getOperation('foo', []) instanceof Operation);

        $this->assertFalse($d->hasOperation("bar"));
        $this->assertNull($d->getOperation('bar'));
    }

    public function testGetErrorHandlers()
    {
        $d = new Description([
            'name' => 'Test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => []
        ]);
        $this->assertEquals($d->getErrorHandlers(), []);

        $d = new Description([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => [],
            'errorHandlers' => [
                'NotFound' => '/path/to/NotFoundException'
            ]
        ]);
        $this->assertTrue(array_key_exists('NotFound', $d->getErrorHandlers()));
    }

    public function testStaticLoader()
    {
        $contents = "<?php return [ 'name' => 'Test', 'baseUrl' => 'http://www.foo.local','operations' => [] ];";
        $file = __DIR__ . "/config.php";

        file_put_contents($file, $contents);

        $d = Description::loadFromFile($file);
        $this->assertEquals($d->getBaseUrl(), 'http://www.foo.local');

        unlink($file);
    }

    public function testStaticLoaderInvalidFile()
    {
        $this->setExpectedException(FileNotFoundException::class);

        $d = Description::loadFromFile('foo');
    }

    public function testNoCache()
    {
        $d = new Description([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => []
        ]);

        $this->assertFalse($d->wantsCache());

        $this->setExpectedException(\InvalidArgumentException::class);
        $d->getCachePool();
    }

    public function testHasCache()
    {
        $d = new Description([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'cache' => [
                'driver' => [
                    'name' => 'Ephemeral',
                    'options' => []
                ]
            ],
            'operations' => []
        ]);

        $this->assertTrue($d->wantsCache());
        $this->assertTrue($d->getCachePool() instanceof Pool);
    }

    public function testInvalidCacheDriver()
    {
        $d = new Description([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'cache' => [
                'driver' => [
                    'name' => 'invalid',
                    'options' => []
                ]
            ],
            'operations' => []
        ]);

        $this->setExpectedException(\InvalidArgumentException::class);
        $d->getCachePool();
    }

    public function testNoCircuitBreaker()
    {
        $d = new Description([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => []
        ]);

        $this->assertFalse($d->wantsCircuitBreaker());

        $this->setExpectedException(\InvalidArgumentException::class);
        $d->getCircuitBreaker();
    }

    public function testHasCircuitBreaker()
    {
        $d = new Description([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'cache' => [
                'driver' => [
                    'name' => 'Ephemeral',
                    'options' => []
                ]
            ],
            'circuitBreaker' => [
                'failureThreshold' => 10
            ],
            'operations' => []
        ]);

        $this->assertTrue($d->wantsCircuitBreaker());
        $this->assertTrue($d->getCircuitBreaker() instanceof CircuitBreaker);
        $this->assertEquals(10, $d->getCircuitBreaker()->getFailureThreshold());
    }
}
