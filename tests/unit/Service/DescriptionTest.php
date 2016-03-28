<?php
namespace STS\Sdk\Service;

use Closure;
use PHPUnit_Framework_TestCase;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Stash\Pool;
use STS\Sdk\CircuitBreaker;
use STS\Sdk\Client;
use STS\Sdk\Pipeline\PipeInterface;
use STS\Sdk\Request;
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

    public function testHasLogger()
    {
        $d = new Description([
            'name' => 'test',
            'baseUrl' => 'http://www.foo.local',
            'operations' => [],
            'logger' => DescriptionTestLogger::class
        ]);

        $this->assertTrue($d->hasLogger());
        $this->assertTrue($d->getLogger() instanceof DescriptionTestLogger);
    }

    public function testHasInvalidLogger()
    {
        $d = new Description([
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

        $d = new Description([
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
}

class DescriptionTestLogger implements LoggerInterface {
    public function emergency($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'emergency';
    }
    public function alert($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'alert';
    }
    public function critical($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'critical';
    }
    public function error($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'error';
    }
    public function warning($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'warning';
    }
    public function notice($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'notice';
    }
    public function info($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'info';
    }
    public function debug($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'debug';
    }
    public function log($level, $message, array $context = array()) {
        return $this->{$level}($message, $context);
    }
}


class DescriptionTestPipe1 implements PipeInterface {
    public function handle(Request $request, Closure $next) {
        $GLOBALS['pipes'][] = "Inside Pipe1";
        return $next($request);
    }
}

class DescriptionTestPipe2 implements PipeInterface {
    public function handle(Request $request, Closure $next) {
        $GLOBALS['pipes'][] = "Inside Pipe2";
        return $next($request);
    }
}
class DescriptionTestPipe3 implements PipeInterface {
    public function handle(Request $request, Closure $next) {
        $GLOBALS['pipes'][] = "Inside Pipe3";
        return $next($request);
    }
}
