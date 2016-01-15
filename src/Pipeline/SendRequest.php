<?php
namespace RC\Sdk\Pipeline;

use Closure;
use GuzzleHttp\Psr7\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\Translation\Translator;

/**
 * Class SendRequest
 * @package RC\Sdk\Pipeline
 */
class SendRequest
{
    /**
     * @param         $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $httpRequest = new Request($request->method, $request->url, $request->headers, $request->body);

        $response = $request->client->send($httpRequest);

        // Try to decode it
        $body = (string) $response->getBody();
        if(is_array(json_decode($body, true))) {
            $body = json_decode($body, true);
        }

        $request->response = $response;
        $request->responseBody = $body;

        return $next($request);
    }
}