<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 1/17/16
 * Time: 3:48 PM
 */

namespace STS\Sdk;

use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use STS\Sdk\Exceptions\ServiceResponseException;

/**
 * Class ErrorHandler
 * @package RC\Sdk
 */
class ErrorHandler
{
    /**
     * @param ClientException $e
     * @param array           $serviceExceptionClasses
     *
     * @return bool
     * @throws ServiceResponseException
     */
    public function handleClientError(ClientException $e, $serviceExceptionClasses = []) {
        $body = json_decode($e->getResponse()->getBody(), true);

        // Make sure we have the data we need
        if (!is_array($body) || !isset($body['error']) || !is_array($body['error']) || !isset($body['error']['type'])) {
            // Huh, ok. Just rethrow the original exception then.
            throw $e;
        }

        $type = $body['error']['type'];
        $message = $body['error']['message'];
        $code = $body['error']['code'];

        // See if our service has a custom error handlers defined for this type
        if(array_key_exists($type, $serviceExceptionClasses)) {
            throw new $serviceExceptionClasses[$type]($message, $code);
        }

        // See if our service has a default we should use
        if(array_key_exists('default', $serviceExceptionClasses)) {
            throw new $serviceExceptionClasses['default']($message, $code);
        }

        // Aight then, we'll use our own default
        throw new ServiceResponseException($message, $code);
    }
}