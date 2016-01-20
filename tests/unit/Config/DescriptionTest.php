<?php
namespace RC\Sdk\Service;

use PHPUnit_Framework_TestCase;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class DescriptionTest extends PHPUnit_Framework_TestCase
{
    public function testInstantiate()
    {
        $d = new Description([
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

        $d = new Description(['baseUrl' => 'foo', 'operations' => false]);
    }

    public function testBaseUrl()
    {
        $d = new Description([
            'baseUrl' => 'http://www.foo.local',
            'operations' => []
        ]);
        $this->assertEquals($d->getBaseUrl(), 'http://www.foo.local');
    }

    public function testGetOperation()
    {
        $d = new Description([
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

        $this->assertTrue($d->getOperation('foo', []) instanceof Operation);
        $this->assertNull($d->getOperation('bar'));
    }

    public function testStaticLoader()
    {
        $contents = "<?php return [ 'baseUrl' => 'http://www.foo.local','operations' => [] ];";
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
}
