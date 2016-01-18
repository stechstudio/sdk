<?php
/**
 * Created by PhpStorm.
 * User: Bubba
 * Date: 1/14/2016
 * Time: 12:21 PM
 */

namespace Sdk\Pipeline;
use GuzzleHttp\Psr7\Uri;
use RC\Sdk\Pipeline\AddSignature;
use RC\Sdk\Request;
use Mockery as m;
use RC\Sdk\Signer;

class SignatureTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Just make sure the header is set, we don't care what the value is
     */
    public function testSignatureAdded()
    {
        $operation = m::mock(Operation::class);
        $operation->shouldReceive("getHttpMethod")->andReturnValues(['GET', 'POST']);

        $uri = m::mock(Uri::class);
        $uri->shouldReceive('getPath');
        $uri->shouldReceive('getQuery');

        $signer = m::mock(Signer::class);
        $signer->shouldReceive('prepareSignatureData');
        $signer->shouldReceive('getSignature')->andReturn('MYSIG');

        $request = m::mock(Request::class);
        $request->shouldReceive('getOperation')->andReturn($operation);
        $request->shouldReceive('getUri')->andReturn($uri);
        $request->shouldReceive('getSigningKey');
        $request->shouldReceive('getBody');

        $request->shouldReceive('setHeader')->withArgs(['X-Signature', 'MYSIG'])->twice();

        $signature = new AddSignature($signer);

        $signature->handle($request, function() { return "result"; });
        $result = $signature->handle($request, function() { return "result"; });

        $this->assertEquals($result, "result");
    }
}
