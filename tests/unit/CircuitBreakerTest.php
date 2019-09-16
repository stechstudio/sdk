<?php
namespace Tests;

use Exception;
use Stash\Driver\Ephemeral;
use Stash\Pool;
use STS\Sdk\CircuitBreaker\Cache;
use STS\Sdk\CircuitBreaker\Monitor;
use STS\Sdk\Service\CircuitBreaker;

class MyCache extends Cache {

}
