<?php
/**
 * Created by PhpStorm.
 * User: josephszobody
 * Date: 3/26/16
 * Time: 3:34 PM
 */

namespace STS\Sdk\Request;

/**
 * Class Uri
 * @package STS\Sdk\Request
 */
class UriBuilder
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * @param $baseUri
     * @param $uri
     * @param $uriData
     * @param $queryData
     *
     * @return string
     */
    public function prepare($baseUri, $uri, $uriData, $queryData)
    {
        return $this
            ->getUriString($baseUri, $uri)
            ->prepareUriString($uriData)
            ->appendQuery($queryData)
            ->get();
    }

    /**
     * @param $baseUri
     * @param $uri
     *
     * @return $this
     */
    protected function getUriString($baseUri, $uri)
    {
        if(strpos($uri, "http") === 0) {
            // The config uri is a full url, just use it
            $this->uri = $uri;
        } else {
            // Otherwise append our uri to the existing baseUri
            $this->uri = $baseUri . $uri;
        }

        return $this;
    }

    /**
     * @param array $uriData
     *
     * @return $this
     */
    protected function prepareUriString(array $uriData)
    {
        $preparedArguments = [];
        foreach($uriData AS $key => $value) {
            $preparedArguments["{" . $key . "}"] = $value;
        }

        $this->uri = str_replace(array_keys($preparedArguments), array_values($preparedArguments), $this->uri);

        return $this;
    }

    /**
     * @param $queryData
     *
     * @return $this
     */
    protected function appendQuery($queryData)
    {
        if(count($queryData)) {
            $this->uri .= "?" . http_build_query($queryData);
        }

        return $this;
    }

    /**
     * @return string
     */
    protected function get()
    {
        return $this->uri;
    }
}