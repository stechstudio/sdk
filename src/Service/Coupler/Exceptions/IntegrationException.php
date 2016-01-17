<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 1/17/16
 * Time: 3:28 PM
 */

namespace RC\Sdk\Service\Coupler\Exceptions;

use RC\Sdk\Exceptions\ApiResponseException;

class IntegrationException extends ApiResponseException {
    protected $code = 2100;
    protected $status = 400;
    protected $message = "Your Dropbox integration is not setup properly";
}