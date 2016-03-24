<?php
namespace STS\Sdk;

use GuzzleHttp\Exception\ClientException;
use STS\Sdk\Exceptions\ServiceResponseException;
use PHPUnit_Framework_TestCase;

class ResponsesTest extends PHPUnit_Framework_TestCase
{
    protected $description = [
        'name' => 'Test',
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
            'remoteErrorWithDefaultException' => [
                'httpMethod' => 'GET',
                'uri' => '/e59a596a-5965-4e00-a1f8-f50474ddd9d3'
            ],
            'remoteErrorWithoutMatchingException' => [
                'httpMethod' => 'GET',
                'uri' => '/c3d5a116-5f92-4056-8cf1-8f74fd243beb'
            ],
            'remoteErrorWithNoBody' => [
                'httpMethod' => 'GET',
                'uri' => '/0704da9e-bdab-40e8-8ac2-7a76bae5f7fa'
            ]
        ],
        'errorHandlers' => [
            'Integration' => CustomException::class,
            'default' => DefaultException::class
        ]
    ];

    /**
     * String response
     */
    public function testBasicResponse()
    {
        $client = new Client($this->description);

        $result = $client->getOk();

        $this->assertEquals("ok", $result);
    }

    /**
     * String response with uri variable
     */
    public function testBasicResponseWithUriVariable()
    {
        $client = new Client($this->description);

        $result = $client->getOkWithVariable(['var' => 'f738e274-ba99-4405-accd-5bfb0358f27b']);

        $this->assertEquals("ok", $result);
    }

    /**
     * JSON response should be decoded
     */
    public function testJsonResponse()
    {
        $client = new Client($this->description);

        $result = $client->getJsonOk();

        $this->assertTrue(is_array($result));
        $this->assertEquals($result['success'], true);
    }

    /**
 * Should get our custom IntegrationException
 */
    public function testRemoteErrorWithMatchingException()
    {
        $client = new Client($this->description);

        $this->setExpectedException(CustomException::class);

        $client->remoteErrorWithMatchingException();
    }

    /**
     * Should get our custom IntegrationException
     */
    public function testRemoteErrorWitDefaultException()
    {
        $client = new Client($this->description);

        $this->setExpectedException(DefaultException::class);

        $client->remoteErrorWithDefaultException();
    }

    /**
     * Should see our base ApiResponseException since we don't have a match
     */
    public function testRemoteErrorWithoutMatchingException()
    {
        $description = $this->description;
        unset($description['errorHandlers']);

        $client = new Client($description);

        $this->setExpectedException(ServiceResponseException::class);

        $client->remoteErrorWithoutMatchingException();
    }

    /**
     * Need to see a guzzle ClientException with there's no error payload that we can interpret
     */
    public function testRemoteErrorWithNoBody()
    {
        $client = new Client($this->description);

        $this->setExpectedException(ClientException::class);

        $client->remoteErrorWithNoBody();
    }
}

class CustomException extends \Exception {
    protected $message = "bar";
}
class DefaultException extends \Exception {
    protected $message = "bar";
}
