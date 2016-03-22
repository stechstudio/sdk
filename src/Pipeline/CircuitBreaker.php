<?php
namespace STS\Sdk\Pipeline;

use Closure;
use STS\Sdk\Request;

class CircuitBreaker implements PipeInterface
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
