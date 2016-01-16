<?php
namespace RC\Sdk\Pipeline;

use Closure;
use RC\Sdk\Request;

/**
 * Class BuildUri
 * @package RC\Sdk\Pipeline
 */
class BuildUri
{
    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $uriArguments = $request->getArguments('uri');
        $uriString = $this->getUriString($request->getBaseUrl(), $request->getConfigUri());

        $uri = $this->prepareUri($uriString, $uriArguments);

        $queryArguments = $request->getArguments('query');

        if(count($queryArguments)) {
            $uri .= "?" . http_build_query($queryArguments);
        }

        $request->setUri($uri);

        return $next($request);
    }

    /**
     * @param $baseUri
     * @param $uri
     *
     * @return string
     */
    protected function getUriString($baseUri, $uri)
    {
        if(strpos($uri, "http") === 0) {
            // The config uri is a full url, just use it
            return $uri;
        }

        // Otherwise append our uri to the existing baseUri
        return $baseUri . $uri;
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