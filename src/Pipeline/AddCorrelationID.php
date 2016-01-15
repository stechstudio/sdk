<?php
namespace RC\Sdk\Pipeline;

use Closure;
use Ramsey\Uuid\Uuid;

/**
 * Class AddCorrelationID
 * @package RC\Sdk\Pipeline
 */
class AddCorrelationID
{
    /**
     * @param         $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $request->setHeader('X-Correlation-ID', $this->getCorrelationID());

        return $next($request);
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