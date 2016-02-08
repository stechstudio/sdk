<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 2/8/16
 * Time: 1:40 PM
 */

namespace STS\Sdk\Exceptions;

use Illuminate\Validation\ValidationException as IlluminateValidationException;

/**
 * Class ValidationException
 * @package STS\Sdk\Exceptions
 */
class ValidationException extends IlluminateValidationException
{
    /**
     * @param \Illuminate\Validation\Validator $validator
     */
    public function __construct($validator)
    {
        $this->validator = $validator;

        // Get a list of invalid keys
        $keys = array_keys($validator->invalid());
        $this->message = 'The following parameters are missing or invalid: ' . implode(',', $keys);
    }

    /**
     * @return \Illuminate\Validation\Validator
     */
    public function getValidator()
    {
        return $this->validator;
    }
}