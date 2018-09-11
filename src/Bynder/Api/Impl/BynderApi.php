<?php

/**
 *
 * Copyright (c) Bynder. All rights reserved.
 *
 * Licensed under the MIT License. For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// src/Bynder/Api/Impl/BynderApi.php
namespace Bynder\Api\Impl;

use Bynder\Api\IBynderApi;
use Bynder\Api\Impl\Oauth\Credentials;
use Bynder\Api\Impl\Oauth\OauthRequestHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use InvalidArgumentException;

/**
 * Implementation of IBynderApi.
 */
class BynderApi implements IBynderApi
{

    /**
     * @var string Base Url necessary for API calls.
     */
    private $baseUrl;
    /**
     * @var AssetBankManager Instance of the Asset bank manager.
     */
    private $assetBankManager;
    /**
     * @var OauthRequestHandler Instance of the Oauth request handler.
     */
    private $requestHandler;

    /**
     * Initialises a new instance of the class.
     *
     * @param string $baseUrl Base Url used for all the requests to the API.
     * @param OauthRequestHandler $requestHandler Instance of the request handler used to communicate with the API.
     *
     */
    public function __construct($baseUrl, OauthRequestHandler $requestHandler)
    {
        $this->baseUrl = $baseUrl;
        $this->requestHandler = $requestHandler;
    }

    /**
     * Creates an instance of BynderApi using the settings provided.
     *
     * @param array $settings Oauth credentials and settings to configure the BynderApi instance.
     * @return BynderApi instance.
     * @throws InvalidArgumentException Oauth settings not valid, consumer key or secret not in array.
     */
    public static function create($settings)
    {
        if (isset($settings) && ($settings = self::validateSettings($settings))) {

            $credentials = new Credentials(
                $settings['consumerKey'],
                $settings['consumerSecret'],
                $settings['token'],
                $settings['tokenSecret']
            );

            $stack = HandlerStack::create(new CurlHandler());
            $stack->push(
                new Oauth1([
                    'consumer_key' => $credentials->getConsumerKey(),
                    'consumer_secret' => $credentials->getConsumerSecret(),
                    'token' => $credentials->getToken(),
                    'token_secret' => $credentials->getTokenSecret(),
                    'request_method' => Oauth1::REQUEST_METHOD_HEADER,
                    'signature_method' => Oauth1::SIGNATURE_METHOD_HMAC
                ])
            );

            $requestOptions = [
                'base_uri' => $settings['baseUrl'],
                'handler' => $stack,
                'auth' => 'oauth',
            ];

            // Configures request Client (adding proxy, etc.)
            if (isset($settings['requestOptions']) && is_array($settings['requestOptions'])) {
                $requestOptions += $settings['requestOptions'];
            }

            $requestClient = new Client($requestOptions);
            $requestHandler = OauthRequestHandler::create($credentials, $settings['baseUrl'], $requestClient);
            return new BynderApi($settings['baseUrl'], $requestHandler);
        } else {
            throw new InvalidArgumentException("Settings passed for BynderApi service creation are not valid.");
        }
    }

    /**
     * Gets an instance of the asset bank manager to use for DAM queries.
     *
     * @return AssetBankManager An instance of the asset bank manager using the request handler previously created.
     */
    public function getAssetBankManager()
    {
        if (!isset($this->assetBankManager)) {
            $this->assetBankManager = new AssetBankManager($this->requestHandler);
        }

        return $this->assetBankManager;
    }

    /**
     * Gets a request token, later used to obtain an access token. This token is only valid for 10 minutes.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function getRequestToken()
    {
        return $this->requestHandler->sendRequestAsync('POST', 'api/v4/oauth/request_token/');
    }

    /**
     * Authorises the request token, this requires the user to login. Passing the a url in the callback parameter will
     * redirect the user to this page after login, otherwise the request responds with the request token.
     *
     * @param $query
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function authoriseRequestToken($query)
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/oauth/authorise/',
            [
                'query' => $query,
                'auth' => null,
                'allow_redirects' => false
            ]);
    }

    /**
     * Exchanges the authorised request token for a valid access token.
     * If successful the request token is immediately expired and the access tokens are set in the credentials.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function getAccessToken()
    {
        return $this->requestHandler->sendRequestAsync('POST', 'api/v4/oauth/access_token/')->then(
            function ($tokenValues) {
                parse_str($tokenValues, $tokenArray);
                $token = $tokenArray['oauth_token'];
                $tokenSecret = $tokenArray['oauth_token_secret'];
                $this->requestHandler->setAccessTokenCredentials($token, $tokenSecret);
                return $tokenArray;
            }
        );
    }

    /**
     * Sets the Access token credentials.
     *
     * @param $token
     * @param $tokenSecret
     */
    public function setAccessTokenCredentials($token, $tokenSecret)
    {
        $this->requestHandler->setAccessTokenCredentials($token, $tokenSecret);
    }

    /**
     * Log in a user with username and password.
     * If successful the retrieves OAUTH access tokens.
     *
     * @deprecated
     *
     * @param $username
     * @param $password
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function userLogin($username, $password)
    {
        return $this->requestHandler->sendRequestAsync('POST', 'api/v4/users/login/',
            [
                'form_params' => [
                    'username' => $username,
                    'password' => $password
                ]
            ])->then(
            function ($result) {
                $this->requestHandler->setAccessTokenCredentials($result['tokenKey'], $result['tokenSecret']);
                return $result;
            }
        );
    }

    /**
     * Log out current user, resetting the access token credentials.
     */
    public function userLogout()
    {
        $this->requestHandler->resetAccessTokenCredentials();
    }

    /**
     * Retrieve all users or specific ones by ID.
     *
     * @param $userId
     * @param $query
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function getUser($userId = '', $query = null)
    {
        return $this->requestHandler->sendRequestAsync('GET', "api/v4/users/$userId",
            [
                'query' => $query
            ]
        );
    }

    /**
     * Retrieve current user.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function getCurrentUser()
    {
        return $this->requestHandler->sendRequestAsync('GET', "api/v4/currentUser/");
    }

    /**
     * Retrieve all users.
     *
     * @param bool $includeInactive Include inactive users.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function getUsers($includeInactive = false)
    {
        if ($includeInactive) {
            $inactive = '1';
        }
        else {
            $inactive = '0';
        }
        return $this->requestHandler->sendRequestAsync('GET', "api/v4/users/?includeInActive=$inactive");
    }

    /**
     * Retrieve all security profiles or specific ones by ID.
     *
     * @param $profileId
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function getSecurityProfile($profileId = '')
    {
        return $this->requestHandler->sendRequestAsync('GET', "api/v4/profiles/$profileId");
    }

    /**
     * Checks if the settings array passed is valid.
     *
     * @param $settings
     * @return bool Whether the settings array is valid.
     */
    private static function validateSettings($settings)
    {
        if (!isset($settings['consumerKey']) || !isset($settings['consumerSecret'])) {
            return false;
        }
        $settings['token'] = isset($settings['token']) ? $settings['token'] : null;
        $settings['tokenSecret'] = isset($settings['tokenSecret']) ? $settings['tokenSecret'] : null;

        return $settings;
    }

    /**
     * Returns the configured request handler
     *
     * @return OauthRequestHandler
     */
    public function getRequestHandler()
    {
        return $this->requestHandler;
    }

}