<?php
namespace STS\Sdk\Pipeline;

use STS\Sdk\Request;
use Closure;

interface PipeInterface
{
    public function handle(Request $request, Closure $next);
}