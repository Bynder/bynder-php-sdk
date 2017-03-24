<?php

/**
 *
 * Copyright (c) Bynder. All rights reserved.
 *
 * Licensed under the MIT License. For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// src/Bynder/Api/Impl/Oauth/IOauthRequestHandler.php
namespace Bynder\Api\Impl\Oauth;

/**
 * Interface to send Oauth requests to the API.
 */
interface IOauthRequestHandler
{

    /**
     * Sends the request to the Bynder API. The requests are all asynchronous and
     * return a promise object, which allows the user to handle the response in
     * any way preferred.
     *
     * @param string $type HTTP verb of the request (GET, POST, etc.).
     * @param string $uri API call endpoint.
     * @param array $query Optional dictionary of params which will be added to the request.
     *
     * @return \GuzzleHttp\Promise\Promise
     * @throws \Exception
     */
    public function sendRequestAsync($type, $uri, $query = null);
}