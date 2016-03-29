<?php
namespace STS\Sdk\Service;

use Closure;
use PHPUnit_Framework_TestCase;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Stash\Pool;
use STS\Sdk\Service\CircuitBreaker;
use STS\Sdk\Client;
use STS\Sdk\Service;
use STS\Sdk\Pipeline\PipeInterface;
use STS\Sdk\Request;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class DescriptionTestLogger implements LoggerInterface {
    public function emergency($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'emergency';
    }
    public function alert($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'alert';
    }
    public function critical($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'critical';
    }
    public function error($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'error';
    }
    public function warning($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'warning';
    }
    public function notice($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'notice';
    }
    public function info($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'info';
    }
    public function debug($message, array $context = array()) {
        $GLOBALS['loglevel'] = 'debug';
    }
    public function log($level, $message, array $context = array()) {
        return $this->{$level}($message, $context);
    }
}


class DescriptionTestPipe1 implements PipeInterface {
    public function handle(Request $request, Closure $next) {
        $GLOBALS['pipes'][] = "Inside Pipe1";
        return $next($request);
    }
}

class DescriptionTestPipe2 implements PipeInterface {
    public function handle(Request $request, Closure $next) {
        $GLOBALS['pipes'][] = "Inside Pipe2";
        return $next($request);
    }
}
class DescriptionTestPipe3 implements PipeInterface {
    public function handle(Request $request, Closure $next) {
        $GLOBALS['pipes'][] = "Inside Pipe3";
        return $next($request);
    }
}
