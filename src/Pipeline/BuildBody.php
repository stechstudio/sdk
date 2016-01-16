<?php
namespace RC\Sdk\Pipeline;

use Closure;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\Translation\Translator;

/**
 * Class BuildBody
 * @package RC\Sdk\Pipeline
 */
class BuildBody
{
    /**
     * @param         $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $bodyArguments = $request->getArguments("body");

        $request->setBody(json_encode($bodyArguments));
        $request->setHeader("Content-Type","application/json");

        return $next($request);
    }
}