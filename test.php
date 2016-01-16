<?php
require 'vendor/autoload.php';

$sdk = \RC\Sdk\Factory::create('Coupler', 'foo');

$sdk->test([]);