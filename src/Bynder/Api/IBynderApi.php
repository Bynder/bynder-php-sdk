<?php

/**
 * Copyright (c) Bynder. All rights reserved.
 * Licensed under the MIT License. For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// src/Bynder/Api/IBynderApi.php
namespace Bynder\Api;

/**
 * Interface to communicate with Bynder API and get instance of AssetBankManager.
 */
interface IBynderApi
{
    /**
     * Get the Bynder Asset Bank Manager which can perform asset bank operations.
     *
     * @return AssetBankManager An instance of the asset bank manager.
     */
    public function getAssetBankManager();

    /**
     * Gets a request token, later used to obtain an access token. This token is only valid for 10 minutes.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getRequestToken();

    /**
     * Authorises the request token, this requires the user to login. Passing the a url in the callback parameter will
     * redirect the user to this page after login, otherwise the request responds with the request token.
     *
     * @param $query
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function authoriseRequestToken($query);

    /**
     * Exchanges the authorised request token for a valid access token.
     * If successful the request token is immediately expired and the access tokens are set in the credentials.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getAccessToken();

    /**
     * Sets the Access token credentials.
     *
     * @param $token
     * @param $tokenSecret
     */
    public function setAccessTokenCredentials($token, $tokenSecret);

    /**
     * Log in a user with username and password.
     * If successful the retrieves OAUTH access tokens.
     *
     * @deprecated
     * @param $username
     * @param $password
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function userLogin($username, $password);

    /**
     * Log out current user, resetting the access token credentials.
     */
    public function userLogout();

}