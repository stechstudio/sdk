<?php
namespace STS\Sdk\Pipeline;

use Closure;
use STS\Sdk\Request;
use STS\Sdk\Request\UriBuilder;

/**
 * Class BuildUri
 * @package RC\Sdk\Pipeline
 */
class BuildUri implements PipeInterface
{
    /**
     * @var UriBuilder
     */
    private $uri;

    /**
     * @param UriBuilder $uri
     */
    public function __construct(UriBuilder $uri)
    {
        $this->uri = $uri;
    }

    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $uri = $this->uri->prepare(
            $request->getDescription()->getBaseUrl(),
            $request->getOperation()->getUri(),
            $request->getOperation()->getDataByLocation("uri"),
            $request->getOperation()->getDataByLocation("query")
        );

        $request->setUri($uri);

        return $next($request);
    }
}