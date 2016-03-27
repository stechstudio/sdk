<?php
namespace STS\Sdk\Pipeline;

use Closure;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use Prophecy\Exception\Exception;
use STS\Sdk\Exceptions\CircuitBreakerOpenException;
use STS\Sdk\Exceptions\ServiceUnavailableException;
use STS\Sdk\Request;

/**
 * Class CircuitBreakerProtection
 * @package STS\Sdk\Pipeline
 */
class CircuitBreakerProtection implements PipeInterface
{
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     * @throws CircuitBreakerOpenException
     * @throws ServiceUnavailableException
     * @throws Exception
     */
    public function handle(Request $request, Closure $next)
    {
        // If this service description doesn't specify a circuit breaker, just move right along
        if (!$request->getDescription()->wantsCircuitBreaker()) {
            return $next($request);
        }

        $circuitBreaker = $request->getDescription()->getCircuitBreaker();

        // If the breaker is already tripped, we halt
        if (!$circuitBreaker->isAvailable()) {
            throw new CircuitBreakerOpenException();
        }

        try {
            $result = $next($request);

            $circuitBreaker->success();

            return $result;

        } catch (ServiceUnavailableException $e) {
            // This is the only exception we consider to mean failure.
            $circuitBreaker->failure();

            throw $e;
        } catch (Exception $e) {
            // All other exceptions we assume are intention errors from the remote service, and we still succeeding
            $circuitBreaker->success();

            throw $e;
        }

    }
}
