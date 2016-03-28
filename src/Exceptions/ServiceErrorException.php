<?php
namespace STS\Sdk\Exceptions;

use Exception;

/**
 * Class ServiceErrorException
 * @package RC\Sdk\Exceptions
 */
class ServiceErrorException extends SdkException
{
    /**
     * @var int HsTTP Status code
     */
    protected $status = 503;
}
