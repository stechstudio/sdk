<?php
namespace STS\Sdk\Pipeline;

use Closure;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use STS\Sdk\Exceptions\ServiceUnavailableException;
use STS\Sdk\Request;

class CircuitBreaker implements PipeInterface
{
    public function handle(Request $request, Closure $next)
    {
        // Before the request, see if the circuit breaker is already open (unavailable) and throw an exception if so
        $breaker = $request->getBreakerSwitch();

        if(!$breaker->isAvailable()) {
            throw new ServiceUnavailableException();
        }

        try {
            $result = $next($request);

            $breaker->success();

            return $result;
            
        } catch(ClientException $e) {
            // This is specifically a 4xx response, which means the server was reached and deliberately
            // returned an error. We consider this a success.
            $breaker->success();

            // Bubble up
            throw $e;

        } catch(BadResponseException $e) {
            // Ok now that we filtered out 4xx above, we can assume this is a 5xx error. A failure.
            $breaker->failure();

            // Throw our own exception, but with previous included
            throw new ServiceUnavailableException("Unable to reach [" . $request->getServiceName() . "]", 503, $e);
        }

    }
}
