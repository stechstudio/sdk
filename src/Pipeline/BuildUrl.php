<?php
namespace RC\Sdk\Pipeline;

use Closure;
use GuzzleHttp\Psr7\Uri;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\Translation\Translator;

/**
 * Class BuildUrl
 * @package RC\Sdk\Pipeline
 */
class BuildUrl
{
    /**
     * @param         $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if(!isset($request->config['uri'])) {
            throw new \InvalidArgumentException("Missing 'uri'");
        }

        $uriArguments = $request->getArguments('uri');
        $uriString = $this->getUriString($request->baseUrl, $request->config['uri']);

        $url = $this->prepareUri($uriString, $uriArguments);

        $queryArguments = $request->getArguments('query');

        if(count($queryArguments)) {
            $url .= "?" . http_build_query($queryArguments);
        }

        $request->url = new Uri($url);

        return $next($request);
    }

    /**
     * @param $baseUrl
     * @param $uri
     *
     * @return string
     */
    protected function getUriString($baseUrl, $uri)
    {
        if(strpos($uri, "http") === 0) {
            // The config uri is a full url, just use it
            return $uri;
        }

        // Otherwise append our uri to the existing baseUrl
        return $baseUrl . $uri;
    }

    /**
     * @param $string
     * @param $arguments
     *
     * @return mixed
     */
    protected function prepareUri($string, $arguments)
    {
        $preparedArguments = [];
        foreach($arguments AS $key => $value) {
            $preparedArguments["{" . $key . "}"] = $value;
        }

        return str_replace(array_keys($preparedArguments), array_values($preparedArguments), $string);
    }
}