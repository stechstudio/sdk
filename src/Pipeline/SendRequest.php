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
        $httpRequest = new Request($request->config['httpMethod'], $request->url, $request->headers, $request->body);

        $response = $request->client->send($httpRequest);

        var_dump($response); die();

        return $next($request);
    }
}