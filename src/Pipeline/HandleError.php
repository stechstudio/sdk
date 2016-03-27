<?php
namespace STS\Sdk\Pipeline;

use Closure;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use STS\Sdk\ErrorParser;
use STS\Sdk\Exceptions\ServiceUnavailableException;
use STS\Sdk\Request;

/**
 * Class HandleError
 * @package STS\Sdk\Pipeline
 */
class HandleError implements PipeInterface
{
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     * @throws ServiceUnavailableException
     * @throws \STS\Sdk\Exceptions\ServiceResponseException
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);

        } catch (ClientException $e) {
            // A ClientException is a 4XX response. We'll hand it to our ErrorParser, which will
            // translate the error to the appropriate local exception.
            (new ErrorParser())->parse(
                $e->getResponse()->getBody(),
                $request->getDescription()->getErrorHandlers()
            );

            // If we're still here, then the ErrorParser couldn't handle it
            throw $e;

        } catch (BadResponseException $e) {
            // This is a 5XX response, a failure to reach the remote service, or a server error
            // at the remote service. Throw our own exception, but with previous included
            throw new ServiceUnavailableException("Unable to reach [" . $request->getServiceName() . "]", 503, $e);
        }
    }
}
