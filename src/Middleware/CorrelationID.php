<?php
namespace Sdk\Middleware;

use Psr\Http\Message\RequestInterface;
use Ramsey\Uuid\Uuid;

/**
 * Class CorrelationID
 * @package Sdk\Middleware
 */
class CorrelationID
{
    /**
     * @param RequestInterface $request
     *
     * @return \Psr\Http\Message\MessageInterface
     */
    public function __invoke(RequestInterface $request)
    {
        return $request->withHeader('X-Correlation-ID', $this->getCorrelationID());
    }

    /**
     * @return \Ramsey\Uuid\UuidInterface|string
     */
    protected function getCorrelationID()
    {
        return (getenv('CORRELATION_ID') === false)
            ? $this->generateCorrelationID()
            : getenv('CORRELATION_ID');
    }

    /**
     * @return \Ramsey\Uuid\UuidInterface
     */
    protected function generateCorrelationID()
    {
        return Uuid::uuid4();
    }
}