<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 3/25/16
 * Time: 2:52 PM
 */

namespace STS\Sdk;

use STS\Sdk\Exceptions\ServiceResponseException;

class ErrorParser
{
    /**
     * @param       $body
     * @param array $serviceExceptionClasses
     *
     * @return bool
     * @throws ServiceResponseException
     */
    public function parse($body, $serviceExceptionClasses = [])
    {
        if(!is_array($body)) {
            $body = json_decode($body, true);
        }

        // Make sure we have the data we need
        if (!is_array($body) || !isset($body['error']) || !is_array($body['error']) || !isset($body['error']['type'])) {
            // Huh, ok. Nothing we can do.
            return false;
        }

        $type = $body['error']['type'];
        $message = $body['error']['message'];
        $code = $body['error']['code'];

        // See if our service has a custom error handlers defined for this type
        if(array_key_exists($type, $serviceExceptionClasses)) {
            throw new $serviceExceptionClasses[$type]($message, $code);
        }

        // Same thing, but see if we have a match without the word "Exception" at the end
        if(ends_with($type, "Exception") && array_key_exists(str_replace("Exception", "", $type), $serviceExceptionClasses)) {
            throw new $serviceExceptionClasses[str_replace("Exception", "", $type)]($message, $code);
        }

        // See if our service has a default we should use
        if(array_key_exists('default', $serviceExceptionClasses)) {
            throw new $serviceExceptionClasses['default']($message, $code);
        }

        // Aight then, we'll use our own default
        throw new ServiceResponseException($message, $code);
    }
}
