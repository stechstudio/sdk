<?php
namespace RC\Sdk\Pipeline;

use Closure;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\Translation\Translator;

/**
 * Class BuildUrl
 * @package RC\Sdk\Pipeline
 */
class BuildUrl
{
    /**
     * @param         $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!isset($request->config['uri'])) {
            throw new \InvalidArgumentException("Missing 'uri'");
        }

        return $next($request);
    }
}