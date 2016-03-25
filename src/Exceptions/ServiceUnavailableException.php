<?php
namespace STS\Sdk\Exceptions;

use Exception;

/**
 * Class ApiResponseException
 * @package RC\Sdk\Exceptions
 */
class ServiceUnavailableException extends SdkException
{
    /**
     * @var int HsTTP Status code
     */
    protected $status = 503;
}
