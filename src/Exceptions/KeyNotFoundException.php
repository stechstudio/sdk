<?php
namespace RC\Sdk\Exceptions;

use Exception;

class KeyNotFoundException extends Exception
{
    protected $message = "Signing key not found. Define an ENV variable or provide one to the client.";
}