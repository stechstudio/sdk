<?php namespace RC\Sdk\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
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
        $container = new Container();
        $pipeline = new Pipeline($container);

        $testBody = "ok";

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $testBody),
            new Response(200, ['X-Foo' => 'Bar'], $testBody)
        ]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);


        $httpClient = new HttpClient(new Container());
        $httpClient->setGuzzle($client);


        $sdk = new MyFirstSdk($httpClient, $pipeline);
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