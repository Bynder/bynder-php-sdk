<?php
/**
 *
 * Copyright (c) Bynder. All rights reserved.
 *
 * Licensed under the MIT License. For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bynder\Api\Impl\PermanentTokens;

class Configuration
{
    private $bynderDomain;

    /**
     * @var string Permanent token.
     */
    private $token;

    /**
     * Initialises a new instance with the specified params.
     *
     * @param string $bynderDomain
     * @param string $token
     * @param array $requestOptions
     */
    public function __construct($bynderDomain, $token, $requestOptions = [])
    {
        $this->bynderDomain = $bynderDomain;
        $this->token = $token;
        $this->requestOptions = $requestOptions;
    }

    public function getBynderDomain()
    {
        return $this->bynderDomain;
    }

    /**
     * Returns the Permanent token.
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

    public function getRequestOptions()
    {
        return $this->requestOptions;
    }

    public function setRequestOptions(array $requestOptions)
    {
        $this->requestOptions = $requestOptions;
    }
}
