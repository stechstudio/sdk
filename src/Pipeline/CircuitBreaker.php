<?php
namespace STS\Sdk\Pipeline;

use Closure;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use STS\Sdk\Exceptions\ServiceUnavailableException;
use STS\Sdk\Request;

/**
 * Class CircuitBreaker
 * @package STS\Sdk\Pipeline
 */
class CircuitBreaker implements PipeInterface
{
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     * @throws ServiceUnavailableException
     */
    public function handle(Request $request, Closure $next)
    {
        // If this service description doesn't specify a circuit breaker, just move right along
        if(!$request->getDescription()->wantsCircuitBreaker()) {
            return $next($request);
        }

        $circuitBreaker = $request->getDescription()->getCircuitBreaker();

        // If the breaker is already tripped, we halt
        if(!$circuitBreaker->isAvailable()) {
            throw new ServiceUnavailableException();
        }

        try {
            $result = $next($request);

            $circuitBreaker->success();

            return $result;

        } catch(ClientException $e) {
            // This is specifically a 4xx response, which means the server was reached and deliberately
            // returned an error. We consider this a success.
            $circuitBreaker->success();

            // Bubble up
            throw $e;

        } catch(BadResponseException $e) {
            // Ok now that we filtered out 4xx above, we can assume this is a 5xx error. A failure.
            $circuitBreaker->failure();

            // Throw our own exception, but with previous included
            throw new ServiceUnavailableException("Unable to reach [" . $request->getServiceName() . "]", 503, $e);
        }

    }
}
