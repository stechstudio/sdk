<?php
require 'vendor/autoload.php';

$sdk = \STS\Sdk\Factory::create('Coupler', 'foo');

$sdk->test([]);