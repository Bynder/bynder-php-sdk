<?php

namespace Bynder\Api\Impl\OAuth2;

use Bynder\Api\Impl\AbstractRequestHandler;
use Bynder\Api\Impl\OAuth2\BynderOauthProvider;

class RequestHandler extends AbstractRequestHandler
{
    const AUTHORIZATION_CODE = 'authorization_code';
    const CLIENT_CREDENTIALS = 'client_credentials';

    protected $configuration;

    private $oauthProvider;

    private $grantType;

    public function __construct($configuration)
    {
        $this->configuration = $configuration;

        $redirectUri = $configuration->getRedirectUri();

        $this->oauthProvider = new BynderOauthProvider([
            'clientId' => $configuration->getClientId(),
            'clientSecret' => $configuration->getClientSecret(),
            'redirectUri' => $redirectUri,
            'bynderDomain' => $configuration->getBynderDomain()
        ]);
        // Switch between authorization_code and client_credentials based on redirectUri
        $this->grantType = trim($redirectUri) ?
            self::AUTHORIZATION_CODE :
            self::CLIENT_CREDENTIALS;
    }

    public function getAuthorizationUrl(array $options = [])
    {
        return $this->oauthProvider->getAuthorizationUrl($options);
    }

    public function getAccessToken($code)
    {
        if ($this->grantType == self::AUTHORIZATION_CODE) {
            if ($code === null || $code === '') {
                //throw exception, code is required when using authorization_code grant type
                throw new \InvalidArgumentException('\'code\' cannot be empty or null when 
                using authorization_code grant type.');
            }
            return $this->oauthProvider->getAccessToken(
                self::AUTHORIZATION_CODE,
                ['code' => $code]
            );
        }
        return $this->oauthProvider->getAccessToken(
            self::CLIENT_CREDENTIALS
        );
    }

    /**
     * This method can be used as a utility method to explicitly
     * choose and set grantType.
     * 
     * @param string $grantType of the oauth flow
     * @throws \InvalidArgumentException
     */
    public function setGrantType($grantType)
    {
        if (
            $grantType !== self::AUTHORIZATION_CODE
            && $grantType !== self::CLIENT_CREDENTIALS
        ) {
            throw new \InvalidArgumentException('This grant type is currently unsupported. 
            Please use only \'authorization_code\' or \'client_credentials\'');
        }
        $this->grantType = $grantType;
    }

    /**
     * This method sets the oauthProvider. This is particularly 
     * useful when injecting mock oauthProviders while testing.
     * 
     * @param OAuthProvider $oauthProvider instance 
     */
    public function setOAuthProvider($oauthProvider)
    {
        $this->oauthProvider = $oauthProvider;
    }

    protected function sendAuthenticatedRequest($requestMethod, $uri, $options = ['headers' => []])
    {
        $this->configuration->refreshToken($this->oauthProvider);
        $updatedHeaders = ['headers' => array_merge(
            $options['headers'],
            [
                'User-Agent' => 'bynder-php-sdk/' . $this->configuration->getSdkVersion()
            ]
        )];
        return $this->oauthProvider->getHttpClient()->sendAsync(
            $this->oauthProvider->getAuthenticatedRequest(
                $requestMethod,
                $uri,
                $this->configuration->getToken()
            ),
            array_merge(
                $options,
                $this->configuration->getRequestOptions(),
                $updatedHeaders
            )
        );
    }
}
