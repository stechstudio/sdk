<?php
namespace RC\Sdk\Pipeline;

use Closure;
use GuzzleHttp\Psr7\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\Translation\Translator;

/**
 * Class CreateSignature
 * @package RC\Sdk\Pipeline
 */
class CreateSignature
{
    /**
     * @param         $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $method = strtoupper($request->config['httpMethod']);
        $path = $request->url->getPath();

        if($method == "GET") {
            $payload = $request->url->getQuery();
        } else {
            $payload = $request->body;
        }

        $request->signature = $this->getSignature($request->signingKey, $this->prepareSignatureData($method, $path, $payload));
        $request->headers['X-Signature'] = $request->signature;

        return $next($request);
    }

    /**
     * @param $method
     * @param $path
     * @param $payload
     *
     * @return string
     */
    protected function prepareSignatureData($method, $path, $payload)
    {
        $hashed = hash("sha256", $payload);
        return $method . "\n" . $path . "\n" . $hashed . "\n" . time();
    }

    /**
     * @param $signingKey
     * @param $data
     *
     * @return string
     */
    protected function getSignature($signingKey, $data)
    {
        return base64_encode(hash_hmac('sha256', (string)$data, $signingKey, true));
    }
}