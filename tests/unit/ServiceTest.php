<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 3/29/16
 * Time: 9:45 AM
 */
namespace STS\Sdk;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Stash\Pool;
use STS\Sdk\Service\CircuitBreaker;
use STS\Sdk\Service\DescriptionTestLogger;
use STS\Sdk\Service\DescriptionTestPipe1;
use STS\Sdk\Service\DescriptionTestPipe2;
use STS\Sdk\Service\DescriptionTestPipe3;
use STS\Sdk\Service\Operation;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class ServiceTest extends PHPUnit_Framework_TestCase
{
    public function testInstantiate()
    {
        $d = new Service([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => []
        ]);
        $this->assertTrue($d instanceof Service);
    }

    public function testEmptyConfig()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $d = new Service([]);
    }

    public function testBadOperationsConfig()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        $d = new Service(['name' => 'test', 'baseUrl' => 'foo', 'operations' => false]);
    }

    public function testTopLevelParameters()
    {
        $d = new Service([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => []
        ]);

        $this->assertEquals('http://www.foo.local', $d->getBaseUrl());
        $this->assertEquals('test', $d->getName());
    }

    public function testGetOperation()
    {
        $d = new Service([
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
        $d = new Service([
            'name' => 'Test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => []
        ]);
        $this->assertEquals($d->getErrorHandlers(), []);

        $d = new Service([
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

        $d = Service::loadFromFile($file);
        $this->assertEquals($d->getBaseUrl(), 'http://www.foo.local');

        unlink($file);
    }

    public function testStaticLoaderInvalidFile()
    {
        $this->setExpectedException(FileNotFoundException::class);

        $d = Service::loadFromFile('foo');
    }

    public function testNoCache()
    {
        $d = new Service([
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
        $d = new Service([
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
        $d = new Service([
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
        $d = new Service([
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
        $d = new Service([
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

    public function testHasLogger()
    {
        $d = new Service([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => [],
            'logger' => DescriptionTestLogger::class,
            'cache' => [
                'driver' => [
                    'name' => 'Ephemeral',
                    'options' => []
                ]
            ],
            'circuitBreaker' => [
                'failureThreshold' => 10
            ],
        ]);

        $this->assertTrue($d->hasLogger());
        $this->assertTrue($d->getLogger() instanceof DescriptionTestLogger);
        $this->assertTrue($d->getCircuitBreaker()->getMonitor()->getLogger() instanceof DescriptionTestLogger);
    }

    public function testHasInvalidLogger()
    {
        $d = new Service([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => [],
            'logger' => Client::class
        ]);

        $this->setExpectedException(\InvalidArgumentException::class);
        $d->getLogger();
    }

    public function testAdditionalPipes()
    {
        $GLOBALS['pipes'] = [];

        $d = new Service([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => [],
            'pipeline' => [
                'prepend' => [
                    DescriptionTestPipe1::class
                ],
                'append' => [
                    DescriptionTestPipe2::class,
                    DescriptionTestPipe3::class
                ]
            ]
        ]);

        $this->assertEquals(1, count($d->getPrependedPipes()));
        $this->assertEquals(2, count($d->getAppendedPipes()));
    }

    public function testGetOptions()
    {
        $d = new Service([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => [],
            'options' => [
                'foo' => 'bar'
            ]
        ]);

        $this->assertEquals("bar", $d->getOptions()['foo']);
    }
}
