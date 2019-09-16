<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 3/26/16
 * Time: 5:01 PM
 */

namespace STS\Sdk\Request;

use Tests\TestCase;

class UriBuilderTest  extends TestCase
{
    protected $uri;

    public function setUp()
    {
        $this->uri = new UriBuilder();
    }

    public function testSimpleUri()
    {
        $this->assertEquals("http://base.local/path", $this->uri->prepare(
            "http://base.local",
            "/path",
            [],
            []
        ));
    }

    public function testUriOverridesBase()
    {
        $this->assertEquals("http://other.local/path", $this->uri->prepare(
            "http://base.local",
            "http://other.local/path",
            [],
            []
        ));
    }

    public function testUrlStringVariables()
    {
        $this->assertEquals("http://base.local/path/5", $this->uri->prepare(
            "http://base.local",
            "/path/{id}",
            ["id" => 5],
            []
        ));
    }

    public function testQueryString()
    {
        $this->assertEquals("http://base.local/path/5?foo=bar", $this->uri->prepare(
            "http://base.local",
            "/path/{id}",
            ["id" => 5],
            ["foo" => "bar"]
        ));
    }
}