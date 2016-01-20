<?php
namespace STS\Sdk\Pipeline;

use Closure;
use STS\Sdk\Request;

/**
 * Class BuildBody
 * @package RC\Sdk\Pipeline
 */
class BuildBody implements PipeInterface
{
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $jsonData = $request->getOperation()->getDataByLocation("json");
        $bodyData = $request->getOperation()->getDataByLocation("body");

        if(count($jsonData)) {
            // We strongly prefer json. If you give us any json at all, it's all we'll use.
            $request->setBody(json_encode($jsonData));
            $request->setHeader("Content-Type","application/json");

        } else if(count($bodyData)) {
            // Otherwise we'll use body data, but I'm not really sure how to do this. Just stick the raw
            // values in as post data? Should I add a line break inbetween each one?
            $request->setBody(implode('', $bodyData));
        }

        // TODO: add support for postField data (application/x-www-form-urlencoded).

        return $next($request);
    }
}