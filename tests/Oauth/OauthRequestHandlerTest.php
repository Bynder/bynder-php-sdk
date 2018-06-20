<?php
namespace Bynder\Test\Oauth;

use Bynder\Api\Impl\Oauth\OauthRequestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class OauthRequestHandlerTest extends TestCase
{

    private function initClient($mockResponse)
    {
        $mock = new MockHandler([$mockResponse]);

        $handler = HandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $mockCredentials = self::getMockBuilder('Bynder\Api\Impl\Oauth\Credentials')
            ->disableOriginalConstructor()
            ->getMock();

        $oauthHandler = new OauthRequestHandler($mockCredentials, 'fakeURL');
        $oauthHandler->initOauthRequestClient($client);

        return $oauthHandler;
    }

    /**
     * Tests a successful request.
     *
     * @throws \Exception
     */
    public function testSuccessfulGetRequest()
    {
        $oauthHandler = $this->initClient(new Response(200, array('Content-type' => 'application/json'),
            json_encode(['dummy' => 'body'])));
        $responsePromise = $oauthHandler->sendRequestAsync('GET', 'test');
        self::assertInstanceOf('GuzzleHttp\Promise\Promise', $responsePromise);
        $response = $responsePromise->wait();
        self::assertNotNull($response['dummy'], "Response body not set");
        self::assertEquals('body', $response['dummy'], "Response body doesn't match the expected value");
    }

    /**
     * Tests for a Client Exception when the response is a 401 code.
     *
     * @throws \Exception
     */
    public function testInvalidSignatureGetRequest()
    {
        $oauthHandler = $this->initClient(new Response(401, ['Content-Length' => 0],
            json_encode(['dummy' => 'body'])));
        $responsePromise = $oauthHandler->sendRequestAsync('GET', 'test');
        self::assertInstanceOf('GuzzleHttp\Promise\Promise', $responsePromise);
        self::expectException('GuzzleHttp\Exception\ClientException');
        $responsePromise->wait();
    }

    /**
     * Test for a Server Exception when the reponse is a 500 code.
     *
     * @throws \Exception
     */
    public function testInternalErrorGetRequest()
    {
        $oauthHandler = $this->initClient(new Response(500, ['Content-Length' => 0],
            json_encode(['dummy' => 'body'])));
        $responsePromise = $oauthHandler->sendRequestAsync('GET', 'test');
        self::assertInstanceOf('GuzzleHttp\Promise\Promise', $responsePromise);
        self::expectException('GuzzleHttp\Exception\ServerException');
        $responsePromise->wait();
    }

    /**
     * Tests for a Request Exception when a more generic error is returned, this includes timeouts, DNS errors, etc.
     *
     * @throws \Exception
     */
    public function testRequestErrorGetRequest()
    {
        $oauthHandler = $this->initClient(new RequestException("Error Communicating with Server",
            new Request('GET', 'test')));
        $responsePromise = $oauthHandler->sendRequestAsync('GET', 'test');
        self::assertInstanceOf('GuzzleHttp\Promise\Promise', $responsePromise);
        self::expectException('GuzzleHttp\Exception\RequestException');
        $responsePromise->wait();
    }

}
