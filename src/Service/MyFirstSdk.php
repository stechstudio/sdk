<?php
namespace RC\Sdk\Service;

use RC\Sdk\AbstractService;

class MyFirstSdk extends AbstractService
{
    /**
     * @var string
     */
    protected $baseUrl = "https://myservice.vpc.rc";

    /**
     * @var array
     */
    protected $description = [
        "doSomething" => [
            "httpMethod" => "POST",
            "uri" => "/dosomething/{id}",
            "parameters" => [
                "domain" => [
                    "validate" => "required|string",
                    "location" => "body"
                ],
                "id" => [
                    "validate" => "required|numeric",
                    "location" => "uri"
                ]
            ]
        ]
    ];

    /**
     * This method is NOT required. The parent __call method would execute `doSomething()` purely
     * based on the above description. However this method allows us to do other stuff first if we want
     * before the base method is executed.
     * @param $params
     */
    public function doSomething($params = [])
    {
        // If it isn't passed in, we know our domain is PLANROOM_HOST environment variable
        if(!array_key_exists("domain", $params)) {
            $params['domain'] = getenv('PLANROOM_HOST');
        }

        return parent::doSomething($params);
    }
}