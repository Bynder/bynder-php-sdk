<?php

/**
 *
 * Copyright (c) Bynder. All rights reserved.
 *
 * Licensed under the MIT License. For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// src/Bynder/Api/Impl/Oauth/Credentials.php
namespace Bynder\Api\Impl\Oauth;

/**
 * Class to hold Oauth tokens necessary for every API request.
 */
class Credentials
{

    /**
     * @var string Consumer key.
     */
    private $consumerKey;
    /**
     * @var string Consumer Secret.
     */
    private $consumerSecret;
    /**
     * @var string Access token.
     */
    private $token;
    /**
     * @var string Access token secret.
     */
    private $tokenSecret;
    /**
     * @var string Initial access token, used for logout.
     */
    private $initialToken;
    /**
     * @var string Initial access token secret, used for logout.
     */
    private $initialSecret;



    /**
     * Initialises a new instance with the specified params.
     *
     * @param string $consumerKey
     * @param string $consumerSecret
     * @param string $token
     * @param string $tokenSecret
     */
    public function __construct($consumerKey, $consumerSecret, $token = null, $tokenSecret = null)
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->token = $token;
        $this->tokenSecret = $tokenSecret;

        $this->initialToken = $token;
        $this->initialSecret = $tokenSecret;
    }

    /**
     * Returns the Consumer Key.
     *
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    /**
     * Returns the Consumer Secret.
     *
     * @return string
     */
    public function getConsumerSecret()
    {
        return $this->consumerSecret;
    }

    /**
     * Returns the Access token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Sets the Access token.
     *
     * @param string $token The Oauth access token.
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Returns the Access token secret.
     *
     * @return string
     */
    public function getTokenSecret()
    {
        return $this->tokenSecret;
    }

    /**
     * Sets the Access token secret.
     *
     * @param string $tokenSecret The Oauth access token secret.
     */
    public function setTokenSecret($tokenSecret)
    {
        $this->tokenSecret = $tokenSecret;
    }

    /**
     * Resets the access credentials.
     */
    public function resetCredentials()
    {
        $this->setToken($this->initialToken);
        $this->setTokenSecret($this->initialSecret);
    }

}
