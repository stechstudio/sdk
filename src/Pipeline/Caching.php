<?php
namespace STS\Sdk\Pipeline;

use Closure;
use STS\Sdk\Request;

class Caching implements PipeInterface
{
    public function handle(Request $request, Closure $next)
    {
        // TODO: examine our operation caching rules, see if we can return a cached result early!
        return $next($request);
    }
}
