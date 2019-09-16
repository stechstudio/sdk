<?php
namespace STS\Sdk;

use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Collection;
use STS\Sdk\Exceptions\ServiceErrorException;
use STS\Sdk\Exceptions\ServiceResponseException;
use PHPUnit_Framework_TestCase;
use STS\Sdk\Response\Model;
use Tests\TestCase;

class ResponsesTest extends TestCase
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
            ],
            'withResponseModel' => [
                'httpMethod' => 'GET',
                'uri' => '/7058b049-ab51-405f-91cc-dbb80abda9cd',
                'response' => [
                    'model' => TestModel::class
                ]
            ],
            'withResponseCollection' => [
                'httpMethod' => 'GET',
                'uri' => '/5d5591c7-0953-44ac-b635-d2e372bc0ca8',
                'response' => [
                    'model' => TestModel::class,
                    'collection' => true
                ]
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

        $this->expectException(CustomException::class);

        $client->remoteErrorWithMatchingException();
    }

    /**
     * Should get our custom IntegrationException
     */
    public function testRemoteErrorWitDefaultException()
    {
        $client = new Client($this->description);

        $this->expectException(DefaultException::class);

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

        $this->expectException(ServiceResponseException::class);

        $client->remoteErrorWithoutMatchingException();
    }

    /**
     * Need to see a guzzle ClientException with there's no error payload that we can interpret
     */
    public function testRemoteErrorWithNoBody()
    {
        $client = new Client($this->description);

        $this->expectException(ServiceErrorException::class);

        $client->remoteErrorWithNoBody();
    }

    public function testModelResponse()
    {
        $client = new Client($this->description);

        $response = $client->withResponseModel();

        $this->assertTrue($response instanceof TestModel);
        $this->assertEquals("bar", $response->foo);
        $response->foo = "something else";
        $this->assertEquals("something else", $response['foo']);

        $this->assertTrue($response->baz['nested']);

        $this->assertEquals(456, $response->qux);
    }

    public function testModelCollection()
    {
        $client = new Client($this->description);

        $response = $client->withResponseCollection();

        $this->assertTrue($response instanceof Collection);
        $this->assertEquals(3, count($response));

        foreach($response AS $model) {
            $this->assertEquals(456, $model->qux);
        }
    }
}

class CustomException extends \Exception {
    protected $message = "bar";
}
class DefaultException extends \Exception {
    protected $message = "bar";
}
class TestModel extends Model {
    public function getQuxAttribute()
    {
        return 456;
    }
}