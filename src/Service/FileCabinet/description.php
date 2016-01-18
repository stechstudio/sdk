<?php
return [
    'baseUrl' => isset($_ENV['FILECABINET_URL']) ? $_ENV['FILECABINET_URL'] : "http://filecabinet.vpc.rc",
    'operations' => [
        "createFile" => [
            "httpMethod" => "POST",
            "uri" => "/files",
            "parameters" => [
                "domain" => [
                    "validate" => "required|string",
                    "location" => "json",
                    "default" => getenv('PLANROOM_HOST'),
                    "sentAs" => "planroom_domain"
                ],
                "path" => [
                    "validate" => "required|string",
                    "location" => "json"
                ],
                "jobId" => [
                    "validate" => "required|numeric",
                    "location" => "json",
                    "sentAs" => "job_id"
                ],
                "sectionId" => [
                    "validate" => "required|numeric",
                    "location" => "json",
                    "sentAs" => "section_id"
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
                "domain" => [
                    "validate" => "required|string",
                    "location" => "json",
                    "default" => getenv('PLANROOM_HOST'),
                    "sentAs" => "planroom_domain"
                ],
                "filters" => [
                    "validate" => "required|string",
                    "location" => "json"
                ],
            ]
        ],
    ]
];