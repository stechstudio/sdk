<?php
namespace STS\Sdk;

use GuzzleHttp\Exception\ClientException;
use STS\Sdk\Exceptions\ApiResponseException;
use STS\Sdk\Factory;
use PHPUnit_Framework_TestCase;
use STS\Sdk\Service\Coupler\Exceptions\IntegrationException;

class ResponsesTest extends PHPUnit_Framework_TestCase
{
    protected $description = [
        'baseUrl' => 'http://mockbin.org/bin',
        'operations' => [
            'getOk' => [
                'httpMethod' => 'GET',
                'uri' => '/f738e274-ba99-4405-accd-5bfb0358f27b'
            ],
            'getOkWithVariable' => [
                'httpMethod' => 'GET',
                'uri' => '/{var}',
                'parameters' => [
                    'var' => [
                        'location' => 'uri'
                    ]
                ]
            ],
            'getJsonOk' => [
                'httpMethod' => 'GET',
                'uri' => '/2af3d6c1-a7bc-4efa-aad9-09ea25515272'
            ],
            'remoteErrorWithMatchingException' => [
                'httpMethod' => 'GET',
                'uri' => '/150a57e4-430d-4ddd-ab26-77111edff8dc'
            ],
            'remoteErrorWithoutMatchingException' => [
                'httpMethod' => 'GET',
                'uri' => '/c3d5a116-5f92-4056-8cf1-8f74fd243beb'
            ],
            'remoteErrorWithNoBody' => [
                'httpMethod' => 'GET',
                'uri' => '/0704da9e-bdab-40e8-8ac2-7a76bae5f7fa'
            ]
        ]
    ];

    /**
     * String response
     */
    public function testBasicResponse()
    {
        $sdk = Factory::createWithDescription($this->description, 'key');
        $result = $sdk->getOk();

        $this->assertEquals("ok", $result);
    }

    /**
     * String response with uri variable
     */
    public function testBasicResponseWithUriVariable()
    {
        $sdk = Factory::createWithDescription($this->description, 'key');
        $result = $sdk->getOkWithVariable(['var' => 'f738e274-ba99-4405-accd-5bfb0358f27b']);

        $this->assertEquals("ok", $result);
    }

    /**
     * JSON response should be decoded
     */
    public function testJsonResponse()
    {
        $sdk = Factory::createWithDescription($this->description, 'key');
        $result = $sdk->getJsonOk();

        $this->assertTrue(is_array($result));
        $this->assertEquals($result['success'], true);
    }

    /**
     * Should get our custom IntegrationException
     */
//    public function testRemoteErrorWithMatchingException()
//    {
//        // For this one I'm going to pretend to be the Coupler service to use a Coupler exception
//        $sdk = Factory::createWithDescription($this->description, 'key');
//        $sdk->setName('Coupler');
//
//        $this->setExpectedException(IntegrationException::class);
//
//        $sdk->remoteErrorWithMatchingException();
//    }

    /**
     * Should see our base ApiResponseException since we don't have a match
     */
    public function testRemoteErrorWithoutMatchingException()
    {
        $sdk = Factory::createWithDescription($this->description, 'key');

        $this->setExpectedException(ApiResponseException::class);

        $sdk->remoteErrorWithoutMatchingException();
    }

    /**
     * Need to see a guzzle ClientException with there's no error payload that we can interpret
     */
    public function testRemoteErrorWithNoBody()
    {
        $sdk = Factory::createWithDescription($this->description, 'key');

        $this->setExpectedException(ClientException::class);

        $sdk->remoteErrorWithNoBody();
    }
}