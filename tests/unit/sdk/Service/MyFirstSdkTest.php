<?php namespace RC\Sdk\Service;

use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use PHPUnit_Framework_TestCase;
use RC\Sdk\HttpClient;


class MyFirstSdkTest extends PHPUnit_Framework_TestCase
{
    // tests
    public function testInstantiation()
    {
        $container = new Container();
        $client = new HttpClient($container);
        $pipeline = new Pipeline($container);
        $sdk = new MyFirstSdk($client, $pipeline);
        $this->assertEquals(MyFirstSdk::class, get_class($sdk));
    }

    public function testCreation()
    {
        $sdk = MyFirstSdk::create();
        $this->assertEquals(MyFirstSdk::class, get_class($sdk));
    }

    public function testCall()
    {
        $testBody = ["test" => "results"];
        $container = new Container();
        $client = new HttpClient($container);
        $pipeline = new Pipeline($container);
        $sdk = new MyFirstSdk($client, $pipeline);
        $sdk->setKey('flartybart');
        $this->assertEquals(MyFirstSdk::class, get_class($sdk));
        $result = $sdk->doSomething(['domain' => 'myunitdomain.php', 'id' => 77, 'name' => 'phpunit']);
        $this->assertEquals('ok', $result, 'We should have gotten our test body back');
        $result = $sdk->doSomethingElse(['domain' => 'myunitdomain.php', 'id' => 77, 'name' => 'phpunit', 'foo' => 'test', 'bar' => 'testing']);
        $this->assertEquals('ok', $result, 'We should have gotten our test body back');
    }

    public function testCallWithoutDomain()
    {
        $container = new Container();
        $client = new HttpClient($container);
        $pipeline = new Pipeline($container);

        putenv('MYFIRSTSDK_KEY=mackflartybart');
        $sdk = new MyFirstSdk($client, $pipeline);
        $this->assertEquals(MyFirstSdk::class, get_class($sdk));
        \putenv('PLANROOM_HOST=http://blarg.php');
        $result = $sdk->doSomething(['id' => 77, 'name' => 'phpunit']);
        $this->assertEquals('ok', $result, 'We should have gotten our test body back');
    }

}