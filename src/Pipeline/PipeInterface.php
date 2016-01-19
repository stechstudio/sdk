<?php
namespace RC\Sdk\Pipeline;

use RC\Sdk\Request;
use Closure;

interface PipeInterface
{
    public function handle(Request $request, Closure $next);
}