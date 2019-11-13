<?php
/**
 *
 * Copyright (c) Bynder. All rights reserved.
 *
 * Licensed under the MIT License. For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bynder\Api\Impl\OAuth2;

use Bynder\Api\Impl\Oauth2\BynderOauthProvider;

/**
 * Class to hold Oauth2 tokens necessary for every API request.
 */
class Configuration
{
    private $bynderDomain;

    private $redirectUri;

    /**
     * @var string Client ID.
     */
    private $clientId;
    /**
     * @var string Client Secret.
     */
    private $clientSecret;
    /**
     * @var string Access token.
     */
    private $token;
    /**
     * @var string Initial access token, used for logout.
     */
    private $initialToken;

    /**
     * Initialises a new instance with the specified params.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $token
     */
    public function __construct($bynderDomain, $redirectUri, $clientId, $clientSecret, $token = null, $requestOptions = [])
    {
        $this->bynderDomain = $bynderDomain;
        $this->redirectUri = $redirectUri;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->token = $token;
        $this->requestOptions = $requestOptions;

        $this->initialToken = $token;
    }

    public function getBynderDomain()
    {
        return $this->bynderDomain;
    }

    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * Returns the Client Key.
     *
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * Returns the Client Secret.
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
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
     * @param string $token The Oauth2 access token.
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Resets the access credentials.
     */
    public function resetCredentials()
    {
        $this->setToken($this->initialToken);
        $this->setTokenSecret($this->initialSecret);
    }

    public function refreshToken(BynderOauthProvider $oauthProvider)
    {
        if(!$this->getToken()->hasExpired()) {
            return;
        }

        $this->setToken(
            $oauthProvider->getAccessToken('refresh_token', [
                'refresh_token' => $this->getToken()->getRefreshToken()
            ])
        );
    }

    public function getRequestOptions()
    {
        return $this->requestOptions;
    }

    public function setRequestOptions(array $requestOptions)
    {
        $this->requestOptions = $requestOptions;
    }
}
