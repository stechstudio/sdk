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
        $urlString = $this->getUrlString($request->baseUrl, $request->config['uri']);
        $url = $this->prepareUrl($urlString, $uriArguments);

        $request->url = $url;

        return $next($request);
    }

    /**
     * @param $baseUrl
     * @param $uri
     *
     * @return string
     */
    protected function getUrlString($baseUrl, $uri)
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
     * @param $string
     * @param $arguments
     *
     * @return mixed
     */
    protected function prepareUrl($string, $arguments)
    {
        $preparedArguments = [];
        foreach($arguments AS $key => $value) {
            $preparedArguments["{" . $key . "}"] = $value;
        }

        return str_replace(array_keys($preparedArguments), array_values($preparedArguments), $string);
    }
}