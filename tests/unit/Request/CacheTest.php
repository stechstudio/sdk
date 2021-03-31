<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 3/26/16
 * Time: 4:45 PM
 */

namespace STS\Sdk\Request;


use GuzzleHttp\Client;
use Stash\Pool;
use STS\Sdk\Request;
use STS\Sdk\Service;
use STS\Sdk\Service\Operation;
use Tests\TestCase;

class CacheTest extends TestCase
{
    protected $request;

    protected $pool;

    protected $data = ["foo" => "bar"];

    protected $key;

    public function setUp(): void
    {
        $this->pool = new Pool();

        $description = \Mockery::mock(Service::class)->makePartial();
        $description->shouldReceive("getCachePool")->andReturn($this->pool);
        $description->shouldReceive("wantsCache")->andReturn(true);

        $operation = \Mockery::mock(Operation::class)->makePartial();
        $operation->shouldReceive("getName")->andReturn("FooOperation");
        $operation->shouldReceive("getData")->andReturn($this->data);
        $operation->shouldReceive("wantsCache")->andReturn(true);
        $operation->shouldReceive('getHttpMethod')->andReturn('POST');

        $this->request = new Request(new Client(), "Foo", $description, $operation, $this->data);

        $this->key = (new Cache($this->pool))->getCacheKey($this->request);
    }

    public function testShouldCache()
    {
        $c = new Cache($this->pool);

        $this->assertTrue($c->shouldCache($this->request));
    }

    public function testGetHasStore()
    {
        $c = new Cache($this->pool);

        $this->assertFalse($c->has($this->request));
        $this->assertNull($c->get($this->request));

        $c->store($this->request, "something");

        $this->assertTrue($c->has($this->request));
        $this->assertEquals("something", $c->get($this->request));

        // Override
        $this->pool->save($this->pool->getItem($this->key)->set("something else"));
        $this->assertEquals("something else", $c->get($this->request));
    }
}
