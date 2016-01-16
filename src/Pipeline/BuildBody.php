<?php
namespace RC\Sdk\Pipeline;

use Closure;
use RC\Sdk\Request;

/**
 * Class BuildBody
 * @package RC\Sdk\Pipeline
 */
class BuildBody
{
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $bodyArguments = $request->getArguments("body");

        $request->setBody(json_encode($bodyArguments));
        $request->setHeader("Content-Type","application/json");

        return $next($request);
    }
}