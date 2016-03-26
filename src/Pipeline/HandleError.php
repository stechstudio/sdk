<?php
namespace STS\Sdk\Pipeline;

use Closure;
use GuzzleHttp\Exception\ClientException;
use STS\Sdk\ErrorParser;
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
     * @throws ClientException
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);

        } catch(ClientException $e) {
            // This will parse the error message and throw the appropriate exception
            (new ErrorParser())->parse(
                $e->getResponse()->getBody(),
                $request->getDescription()->getErrorHandlers()
            );

            // If we're still here, then the ErrorParser couldn't handle it
            throw $e;
        }
    }
}
