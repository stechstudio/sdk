<?php
namespace RC\Sdk\Pipeline;

use Closure;
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

        $uriParams = $this->getUriParams($request->config['parameters']);
        $uriArguments = $this->getUriArguments($uriParams, $request->arguments);
        $uriString = $this->getUriString($request->baseUrl, $request->config['uri']);

        $url = $this->prepareUri($uriString, $uriArguments);

        $queryParams = $this->getQueryParams($request->config['parameters']);
        $queryArguments = $this->getQueryArguments($queryParams, $request->arguments);

        if(count($queryArguments)) {
            $url .= "?" . http_build_query($queryArguments);
        }

        $request->url = $url;

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
     * Return a simple array of parameter names that should be located in our uri
     *
     * @param $parameters
     *
     * @return array
     */
    protected function getUriParams($parameters)
    {
        return array_keys(array_filter($parameters, function($details) {
            return $details['location'] == 'uri';
        }));
    }

    /**
     * Return a simple array of parameter names that should be located in our query
     *
     * @param $parameters
     *
     * @return array
     */
    protected function getQueryParams($parameters)
    {
        return array_keys(array_filter($parameters, function($details) {
            return $details['location'] == 'query';
        }));
    }

    /**
     * @param $uriParams
     * @param $arguments
     *
     * @return array
     */
    protected function getUriArguments($uriParams, $arguments)
    {
        return array_intersect_key($arguments, array_flip($uriParams));
    }

    /**
     * @param $queryParams
     * @param $arguments
     *
     * @return array
     */
    protected function getQueryArguments($queryParams, $arguments)
    {
        return array_intersect_key($arguments, array_flip($queryParams));
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