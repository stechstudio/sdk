<?php
namespace RC\Sdk\Pipeline;

use Closure;
use GuzzleHttp\Psr7\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use RC\Sdk\Signer;
use Symfony\Component\Translation\Translator;

/**
 * Class AddSignature
 * @package RC\Sdk\Pipeline
 */
class AddSignature
{
    protected $signer;

    /**
     * AddSignature constructor.
     *
     * @param Signer $signer
     */
    public function __construct(Signer $signer)
    {
        $this->signer = $signer;
    }

    /**
     * @param         $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();

        if($method == "GET") {
            $payload = $request->getUri()->getQuery();
        } else {
            $payload = $request->getBody();
        }

        $request->setHeader('X-Signature', $this->signer->getSignature($request->getSigningKey(), $this->signer->prepareSignatureData($method, $path, $payload)));

        return $next($request);
    }
}