<?php
return [
    'baseUrl' => isset($_ENV['COUPLER_URL']) ? $_ENV['COUPLER_URL'] : 'http://coupler.vpc.rc',
    'operations' => [
        'syncEfsFile' => [
            'httpMethod' => 'POST',
            'uri' => isset($_ENV['COUPLER_MQ_WEBHOOK']) ? $_ENV['COUPLER_MQ_WEBHOOK'] : 'https://mq-aws-us-east-1-1.iron.io/3/projects/569967544826aa000600005f/queues/push-qa/webhook?oauth=18AlckkDW2kdMRx1Lx50hw1XR4g',
            'parameters' => [
                "domain" => [
                    "validate" => "required|string",
                    "location" => "body"
                ],
                "path" => [
                    "validate" => "required|string",
                    "location" => "body"
                ]
            ],
        ],
        'test' => [
            'httpMethod' => 'POST',
            'uri' => 'http://requestb.in/1bacfb31',
            'parameters' => [
                'domain' => [
                    'validate' => 'required|string',
                    'default' => 'www.foo.com',
                ]
            ]
        ],
        'getAuthorizeUrl' => [
            'httpMethod' => 'GET',
            'uri' => '/authorization-url',
            'parameters' => [
                'domain' => [
                    'validate' => 'required',
                    'location' => 'query'
                ],
                'redirectUri' => [
                    'validate' => 'required',
                    'location' => 'query',
                ]
            ],
        ],
        'finishAuthorization' => [
            'httpMethod' => 'POST',
            'uri' => '/integrations',
            'parameters' => [
                'redirectUri' => [
                    'validate' => 'required',
                    'location' => 'body',
                ],
                'csrfToken' => [
                    'validate' => 'required',
                    'location' => 'body',
                ],
                'queryParams' => [
                    'validate' => 'required',
                    'location' => 'body',
                ],
            ],
        ],
        'retrieveAuthorization' => [
            'httpMethod' => 'GET',
            'uri' => '/integrations/{id}',
            'parameters' => [
                'id' => [
                    'validate' => 'required',
                    'location' => 'uri',
                ]
            ],
        ],
    ]
];