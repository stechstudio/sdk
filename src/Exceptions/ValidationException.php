<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 2/8/16
 * Time: 1:40 PM
 */

namespace STS\Sdk\Exceptions;

use Illuminate\Contracts\Support\MessageProvider;
use Illuminate\Validation\ValidationException as IlluminateValidationException;

/**
 * Class ValidationException
 * @package STS\Sdk\Exceptions
 */
class ValidationException extends IlluminateValidationException
{

    public function __construct(MessageProvider $provider)
    {
        $this->message = 'The following parameters are missing or invalid: ' . implode(', ', $provider->keys());

        parent::__construct($provider);
    }
}
