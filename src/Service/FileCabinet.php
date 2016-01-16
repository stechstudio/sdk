<?php namespace Sdk\Service;

use RC\Sdk\AbstractService;

class FileCabinet extends AbstractService {

    /**
     * @var string
     */
    protected $baseUrl = "http://filecabinet.vpc.rc";

    /**
     * @var array
     */
    protected $description = [
    "createFile" => [
        "httpMethod" => "POST",
        "uri" => "/files",
        "parameters" => [
            "planroom_domain" => [
                "validate" => "required|string",
                "location" => "body"
            ],
            "path" => [
                "validate" => "required|string",
                "location" => "body"
            ],
            "job_id" => [
                "validate" => "required|numeric",
                "location" => "body"
            ],
            "section_id" => [
                "validate" => "required|numeric",
                "location" => "body"
            ],
        ]
    ],
    "getFile" => [
        "httpMethod" => "GET",
        "uri" => "/files/{id}",
        "parameters" => [
            "id" => [
                "validate" => "required|numeric",
                "location" => "uri"
            ],
        ]
    ],
    "deleteFile" => [
        "httpMethod" => "DELETE",
        "uri" => "/files/{id}",
        "parameters" => [
            "id" => [
                "validate" => "required|numeric",
                "location" => "uri"
            ],
        ]
    ],
    "updateFile" => [
        "httpMethod" => "PATCH",
        "uri" => "/files/{id}",
        "parameters" => [
            "id" => [
                "validate" => "required|numeric",
                "location" => "uri"
            ],
        ]
    ],
    "searchFiles" => [
        "httpMethod" => "POST",
        "uri" => "/files/searches",
        "parameters" => [
            "planroom_domain" => [
                "validate" => "required|string",
                "location" => "body"
            ],
            "filters" => [
                "validate" => "required|string",
                "location" => "body"
            ],
        ]
    ],

];
}