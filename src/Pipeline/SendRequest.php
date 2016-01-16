<?php
namespace RC\Sdk\Pipeline;

use Closure;
use RC\Sdk\Request;

/**
 * Class SendRequest
 * @package RC\Sdk\Pipeline
 */
class SendRequest
{
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $request->send();

        // Try to decode it
        $body = (string) $response->getBody();
        if(is_array(json_decode($body, true))) {
            $body = json_decode($body, true);
        }

        $request->saveResponse($response, $body);

        return $next($request);
    }
}