<?php
namespace STS\Sdk\Exceptions;

use Exception;

/**
 * Class ApiResponseException
 * @package RC\Sdk\Exceptions
 */
class ServiceResponseException extends SdkException
{
    /**
     * @var int HTTP Status code
     */
    protected $status = 400;
}
