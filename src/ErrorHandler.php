<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 1/17/16
 * Time: 3:48 PM
 */

namespace RC\Sdk;

use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ErrorHandler
 * @package RC\Sdk
 */
class ErrorHandler
{
    /**
     * At moment I'm only interested in ClientExceptions. I think. We'll see.
     *
     * @param                 $serviceName
     * @param ClientException $e
     */
    public function handle($serviceName, ClientException $e) {
        $this->parseResponseAndHandle($serviceName, $e->getResponse());

        // Apparently we didn't know what to do with it, just rethrow
        throw $e;
    }

    /**
     * @param                   $serviceName
     * @param ResponseInterface $response
     *
     * @return bool
     */
    protected function parseResponseAndHandle($serviceName, ResponseInterface $response) {
        $body = json_decode($response->getBody(), true);

        if (!is_array($body) || !isset($body['error']) || !is_array($body['error']) || !isset($body['error']['type'])) {
            return false;
        }

        // We're going to search for a matching exception class. First in a service-specific location, then
        // the default SDK location

        $type = $body['error']['type'];
        $message = $body['error']['message'];
        $code = $body['error']['code'];

        $serviceNamespace = 'RC\Sdk\Service\\' . $serviceName . '\Exceptions\\';
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