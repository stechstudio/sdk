<?php
namespace Tests;

class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function tearDown()
    {
        \Mockery::close();
    }
}