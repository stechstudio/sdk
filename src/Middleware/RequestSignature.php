<?php
namespace RC\Sdk\Middleware;

use Psr\Http\Message\RequestInterface;
use Ramsey\Uuid\Uuid;

/**
 * Class RequestSignature
 * @package Sdk\Middleware
 */
class RequestSignature
{
    /**
     * @param RequestInterface $request
     *
     * @return \Psr\Http\Message\MessageInterface
     */
    public function __invoke(RequestInterface $request)
    {
        var_dump((string)$request->getBody()); die();
        //return $request->withHeader('X-Correlation-ID', $this->getCorrelationID());
    }
}