<?php
namespace STS\Sdk\Pipeline;

use Closure;
use STS\Sdk\Exceptions\ServiceUnavailableException;
use STS\Sdk\Request;
use STS\Sdk\Request\UriBuilder;

/**
 * Class LogRequest
 * @package RC\Sdk\Pipeline
 */
class LogRequest implements PipeInterface
{
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     * @throws ServiceUnavailableException
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next)
    {
        if(!$request->getDescription()->hasLogger()) {
            return $next($request);
        }

        $logger = $request->getDescription()->getLogger();
        $logger->debug("Preparing to call " . $request->getServiceName() . "::" . $request->getOperation()->getName());

        try {
            $result = $next($request);
            $logger->info("Successful call to  " . $request->getServiceName() . "::" . $request->getOperation()->getName());
            return $result;

        } catch(ServiceUnavailableException $e) {
            $logger->error("Error reaching " . $request->getServiceName() . "::" . $request->getOperation()->getName(), ["exception" => $e->getPrevious()]);

            throw $e;
        }
    }
}