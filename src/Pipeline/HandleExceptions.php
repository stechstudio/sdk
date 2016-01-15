<?php
namespace RC\Sdk\Pipeline;

use Closure;
use GuzzleHttp\Psr7\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use RC\Sdk\Exceptions\ApiResponseException;
use Symfony\Component\Translation\Translator;

/**
 * The goal here is to look for errors from the remote service, and see if we have a matching
 * Exception class defined locally. If we do, throw it!
 *
 * Class HandleExceptions
 * @package RC\Sdk\Pipeline
 */
class HandleExceptions
{
    /**
     * @param         $request
     * @param Closure $next
     *
     * @return mixed
     * @throws ApiResponseException
     */
    public function handle($request, Closure $next)
    {
        $response = $request->getResponseBody();

        if (is_array($response) && isset($response['error']) && is_array($response['error']) && isset($response['error']['type'])) {
            // We're going to search for a matching exception class. First in a service-specific location, then
            // the default SDK location

            $type = $response['error']['type'];
            $message = $response['error']['message'];
            $code = $response['error']['code'];

            $serviceNamespace = 'RC\Sdk\Service\\' . $request->getServiceName() . '\Exceptions\\';
            $defaultNamespace = 'RC\Sdk\Exceptions\\';

            $this->throwIfFound($serviceNamespace . $type, $message, $code);
            $this->throwIfFound($defaultNamespace . $type, $message, $code);

            // No match? Hmm, it's possible that the remote error is something like "ValidationError" and yet the exception
            // class would be "ValidationErrorException"

            $this->throwIfFound($serviceNamespace . $type . "Exception", $message, $code);
            $this->throwIfFound($defaultNamespace . $type . "Exception", $message, $code);

            // Still no match? Ok we'll throw our default ApiResponseException, while still allowing for a service-specific one first

            $this->throwIfFound($serviceNamespace . 'ApiResponseException', $message, $code);
            $this->throwIfFound($defaultNamespace . 'ApiResponseException', $message, $code);
        }

        return $next($request);
    }

    /**
     * @param $exception
     * @param $message
     * @param $code
     */
    protected function throwIfFound($exception, $message, $code)
    {
        if (class_exists($exception, true)) {
            throw new $exception($message, $code);
        }
    }
}