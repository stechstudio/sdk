<?php
namespace RC\Sdk\Pipeline;

use Closure;

class ValidateParameters
{
    protected $validator;

    public function __construct()
    {

    }

    public function handle($request, Closure $next)
    {
        // TODO: run validation, and throw an exception if it fails

        return $next($request);
    }
}