<?php
/**
 * Created by PhpStorm.
 * User: Bubba
 * Date: 1/14/2016
 * Time: 12:21 PM
 */

namespace Sdk\Middleware;


use GuzzleHttp\Psr7\Request;
use RC\Sdk\Middleware\CorrelationID;

class CorrelationIDTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiation()
    {
        $correlationID = new CorrelationID();
        $request = new Request('GET', 'http://php.unit/test');
        $request = $correlationID($request);
        $this->assertNotEmpty($request->getHeader('X-Correlation-ID'));
    }
}
