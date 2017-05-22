<?php

/**
 *
 * Copyright (c) Bynder. All rights reserved.
 *
 * Licensed under the MIT License. For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// src/Bynder/Api/Impl/Oauth/AssetBankManager.php
namespace Bynder\Api\Impl\Oauth;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Psr\Http\Message\ResponseInterface;

/**
 * Implementation of IOauthRequestHandler. Using Guzzle as HTTP library.
 */
class OauthRequestHandler implements IOauthRequestHandler
{

    /**
     * @var string API base url, used for all calls.
     */
    private $baseUrl;
    /**
     * @var Credentials Instance of credentials, used for oauth purposes.
     */
    private $credentials;
    /**
     * @var Client The http client used for HTTP requests.
     */
    private $oauthRequestClient;

    /**
     * Initialises an instance of OauthRequestHandler.
     *
     * @param Credentials $credentials
     * @param string $baseUrl
     */
    public function __construct(Credentials $credentials, $baseUrl)
    {
        $this->credentials = $credentials;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Creates an instance of OauthRequestHandler using the settings provided.
     *
     *
     * @param Credentials $credentials The Bynder oauth credentials.
     * @param string $baseUrl Api base url used for all requests.
     * @param Client $client Optional client passed for handler creation.
     *
     * @return OauthRequestHandler An instance of the request handler properly configured.
     */
    public static function create(Credentials $credentials, $baseUrl, Client $client = null)
    {

        $newOauthHandler = new OauthRequestHandler($credentials, $baseUrl);
        $newOauthHandler->initOauthRequestClient($client);

        return $newOauthHandler;
    }

    /**
     * Gets the Oauth Credentials.
     *
     * @return Credentials
     */
    public function getCredentials()
    {
        return $this->credentials;
    }

    /**
     * Sets the Access token credentials and re-initialises the request client.
     *
     * @param $token
     * @param $tokenSecret
     */
    public function setAccessTokenCredentials($token, $tokenSecret)
    {
        $this->credentials->setToken($token);
        $this->credentials->setTokenSecret($tokenSecret);
        $this->initOauthRequestClient();
    }

    /**
     * Resets the access token credentials.
     */
    public function resetAccessTokenCredentials()
    {
        $this->credentials->resetCredentials();
        $this->initOauthRequestClient();
    }

    public function initOauthRequestClient(Client $client = null)
    {

        if (!isset($client)) {
            $stack = HandlerStack::create(new CurlHandler());
            $stack->push(
                new Oauth1([
                    'consumer_key' => $this->credentials->getConsumerKey(),
                    'consumer_secret' => $this->credentials->getConsumerSecret(),
                    'token' => $this->credentials->getToken(),
                    'token_secret' => $this->credentials->getTokenSecret(),
                    'request_method' => Oauth1::REQUEST_METHOD_HEADER,
                    'signature_method' => Oauth1::SIGNATURE_METHOD_HMAC
                ])
            );
            $this->oauthRequestClient = new Client([
                'base_uri' => $this->baseUrl,
                'handler' => $stack,
                'auth' => 'oauth'
            ]);
        } else {
            $this->oauthRequestClient = $client;
        }
    }

    /**
     * Sends a request to the Bynder API. All requests are async for now and the
     * query array is parsed as request filter.
     *
     * @param string $type
     * @param string $uri
     * @param array $options
     *
     * @return PromiseInterface
     * @throws Exception
     */
    public function sendRequestAsync($type, $uri, $options = null)
    {
        $request = null;
        switch ($type) {
            case 'GET':
                $request = $this->oauthRequestClient
                    ->getAsync($uri, $options);
                break;
            case 'POST':
                $request = $this->oauthRequestClient
                    ->postAsync($uri, $options);
                break;
            case 'DELETE':
                $request = $this->oauthRequestClient
                    ->deleteAsync($uri, $options);
                break;
            default :
                throw new Exception("The request type you entered is not valid.");
                break;
        }
        return $request->then(
            function (ResponseInterface $response) {
                $contentType = self::checkResponseContentType($response->getHeader('Content-Type'));
                switch ($contentType) {
                    case 'json':
                        return json_decode($response->getBody(), true);
                        break;
                    case 'string':
                        return (string)$response->getBody();
                        break;
                    case 'html':
                        return $response;
                        break;
                    default:
                        throw new Exception("The response type not recognized.");
                }
            }
        );
    }

    private static function checkResponseContentType($contentType)
    {
        if ($contentType && strpos($contentType[0], 'application/json') === 0) {
            return 'json';
        } elseif ($contentType && strpos($contentType[0], 'text/plain') === 0) {
            return 'string';
        } elseif ($contentType && strpos($contentType[0], 'text/html') === 0) {
            return 'html';
        }
        return false;
    }
}