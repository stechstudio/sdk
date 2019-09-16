<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 4/2/16
 * Time: 2:02 PM
 */

namespace Tests\Response;

use Illuminate\Support\Collection;
use STS\Sdk\Response\Builder;
use STS\Sdk\Response\Model;
use Tests\TestCase;

class BuilderTest extends TestCase
{
    public function testSingle()
    {
        $response = [
            'first_name' => 'John'
        ];

        $model = (new Builder())->single(TestBuilderModel::class, $response);
        $this->assertTrue($model instanceof TestBuilderModel);
        $this->assertEquals("John", $model->first_name);
    }

    public function testCollection()
    {
        $response = [
            ['first_name' => 'John'],
            ['first_name' => 'James']
        ];

        $result = (new Builder())->collection(TestBuilderModel::class, $response);
        $this->assertTrue($result instanceof Collection);

        $this->assertEquals(2, $result->count());
        $this->assertEquals(2, (count($result)));

        $model = $result->shift();
        $this->assertTrue($model instanceof TestBuilderModel);
        $this->assertEquals("John", $model->first_name);
    }
}

class TestBuilderModel extends Model {

}