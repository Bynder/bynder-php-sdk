<?php

namespace Bynder\Api;

use Bynder\Api\Impl\OAuth2\BynderOauthProvider;

class RequestHandler
{

    private $configuration;
    private $oauthProvider;

    public function __construct($configuration)
    {
        $this->configuration = $configuration;

        $this->oauthProvider = new BynderOauthProvider([
            'clientId' => $configuration->getClientId(),
            'clientSecret' => $configuration->getClientSecret(),
            'redirectUri' => $configuration->getRedirectUri(),
            'bynderDomain' => $configuration->getBynderDomain()
        ]);
    }

    public function getAuthorizationUrl(array $options = [])
    {
        return $this->oauthProvider->getAuthorizationUrl($options);
    }

    public function getAccessToken($code)
    {
        return $this->oauthProvider->getAccessToken(
            'authorization_code',
            ['code' => $code]
        );
    }

    public function sendRequestAsync($requestMethod, $uri, $options = [])
    {
        $this->configuration->refreshToken($this->oauthProvider);

        $uri = sprintf(
            'https://%s/%s', $this->configuration->getBynderDomain(), $uri
        );

        if (!in_array($requestMethod, ['GET', 'POST', 'DELETE'])) {
            throw new Exception('Invalid request method provided');
        }

        $request = $this->sendAuthenticatedRequest($requestMethod, $uri, $options);

        return $request->then(
            function ($response) {
                switch (self::checkResponseContentType($response->getHeader('Content-Type'))) {
                    case 'json':
                        return json_decode($response->getBody(), true);
                    case 'string':
                        return (string)$response->getBody();
                    case 'html':
                        return $response;
                    default:
                        throw new Exception("The response type not recognized.");
                }
            }
        );
    }

    private function sendAuthenticatedRequest($requestMethod, $uri, $options = [])
    {
        return $this->oauthProvider->getHttpClient()->sendAsync(
            $this->oauthProvider->getAuthenticatedRequest(
                $requestMethod, $uri, $this->configuration->getToken()
            ),
            array_merge($options, $this->configuration->getRequestOptions())
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
        return '';
    }
}
