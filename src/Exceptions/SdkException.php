<?php
namespace STS\Sdk\Exceptions;

use Exception;

/**
 * Class SdkException
 * @package RC\Sdk\Exceptions
 */
class SdkException extends Exception
{
    /**
     * @var int HTTP Status code
     */
    protected $status = 400;

    /**
     * Just to be a bit more explicit about what code we're talking about
     * @return int|mixed
     */
    public function getErrorCode()
    {
        return $this->getCode();
    }
    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
    /**
     * Just to be a bit more explicit about what code we're talking about
     * @return mixed
     */
    public function getHttpStatusCode()
    {
        return $this->getStatus();
    }

    /**
     * @return string
     */
    public function getErrorName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
