<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 4/2/16
 * Time: 1:40 PM
 */

namespace STS\Sdk\Response;


use Tests\TestCase;

class ModelTest extends TestCase
{
    public function testModel()
    {
        $attributes = [
            "first_name" => "John",
            "last_name" => "Doe",
            "email" => "j.doe@example.org",
            "company" => [
                "name" => "Foo Company",
            ]
        ];

        $user = new TestUserModel($attributes);

        $this->assertEquals("John", $user->first_name);
        $this->assertTrue(isset($user['first_name']));
        $this->assertEquals("John", $user['first_name']);
        $this->assertNull($user->invalid);

        $this->assertEquals("John Doe", $user->name);

        $this->assertTrue($user->company instanceof TestCompanyModel);
        $this->assertEquals("Foo Company", $user->company->name);

        $this->assertEquals($attributes, $user->toArray());
        $this->assertTrue(is_string($user->toJson()));

        $user['first_name'] = "James";
        $this->assertEquals("James", $user->first_name);

        $user->phone = "123-456-7890";
        $this->assertEquals("123-456-7890", $user->phone);

        $user->name = "Another Person";
        $this->assertEquals("Another", $user->first_name);

        unset($user['email']);
        $this->assertNull($user->email);

        unset($user['company']);
        $this->assertNull($user->company);
    }
}


class TestUserModel extends Model {
    protected $relatedModels = [
        'company' => TestCompanyModel::class
    ];

    public function getNameAttribute()
    {
        return $this->first_name . " " . $this->last_name;
    }

    public function setNameAttribute($value)
    {
        list($this->first_name, $this->last_name) = explode(" ", $value);
    }
}

class TestCompanyModel extends Model {

}