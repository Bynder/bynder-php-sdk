<?php

namespace Bynder\Test\Bynder\Api;

use PHPUnit\Framework\TestCase;
use Bynder\Api\BynderClient;
use Bynder\Api\Impl\OAuth2\RequestHandler;

use Bynder\Api\Impl\OAuth2;

class BynderClientTest extends TestCase
{
    const CODE = 'dummy-test-code';
    const ACCESS_TOKEN = 'dummy-test-access_token';

    const BYNDER_DOMAIN = 'test.getbynder.com';
    const CLIENT_ID = 'clientId';
    const CLIENT_SECRET = 'clientSecret';
    const REDIRECT_URI = 'test.com/callback';

    public function setUp()
    {
        $this->configuration = new OAuth2\Configuration(
            self::BYNDER_DOMAIN,
            self::REDIRECT_URI,
            self::CLIENT_ID,
            self::CLIENT_SECRET,
            null
        );
        $this->bynderClient = new BynderClient($this->configuration);
    }

    public function testAllowedConfigurationOptions()
    {
        self::assertInstanceOf(
            'Bynder\Api\BynderClient',
            new BynderClient(
                new OAuth2\Configuration(
                    self::BYNDER_DOMAIN,
                    '',
                    '',
                    '',
                    ''
                )
            )
        );
    }

    public function testGetAssetBankManager()
    {
        $assetBankManager = $this->bynderClient->getAssetBankManager();

        self::assertInstanceOf(
            'Bynder\Api\Impl\AssetBankManager',
            $assetBankManager
        );

        self::assertSame(
            $assetBankManager,
            $this->bynderClient->getAssetBankManager()
        );
    }

    public function testGetAuthorizationUrl()
    {
        $authorizationUrl = $this->bynderClient->getAuthorizationUrl(['openid', 'offline']);
        $parsedUrl = parse_url($authorizationUrl);

        self::assertNotEquals($parsedUrl, false);

        parse_str($parsedUrl['query'], $queryParams);

        self::assertSame(
            $parsedUrl['host'],
            $this->configuration->getBynderDomain()
        );
        self::assertSame(
            $queryParams['redirect_uri'],
            $this->configuration->getRedirectUri()
        );
        self::assertSame(
            $queryParams['client_id'],
            $this->configuration->getClientId()
        );
        self::assertSame(
            $queryParams['scope'],
            'openid offline'
        );
    }

    /**
     * Tests the whether the access token is correctly generated and
     * fetched for OAuth2 when grantType used is client_credentials.
     * 
     * @covers \Bynder\Api\Impl\OAuth2\RequestHandler::getAccessToken()
     * @throws \InvalidArgumentException
     */
    public function testGetAccessTokenClientCredentials()
    {
        // This should follow client_credentials flow as redirectUri is invalid
        $requestHandler = $this->setUpRequestHandler('client_credentials', ' ');
        $this->getAccessToken($requestHandler);
        
        // This should follow client_credentials flow as redirectUri is missing
        $requestHandler = $this->setUpRequestHandler('client_credentials');
        $this->getAccessToken($requestHandler);
    }

    /**
     * Tests the whether the access token is correctly generated and
     * fetched for OAuth2 when grantType used is authorization_code.
     * 
     * @covers \Bynder\Api\Impl\OAuth2\RequestHandler::getAccessToken()
     * @throws \InvalidArgumentException
     */
    public function testGetAccessTokenAuthorizationCode()
    {
        $requestHandler = $this->setUpRequestHandler(
            'authorization_code',
            'test.com/callback',
            [
                "code" => self::CODE
            ]
        );
        $this->getAccessToken($requestHandler);
    }

    /**
     * Tests the whether an exception is thrown for OAuth2 when 
     * grantType used is authorization_code and no code is passed.
     * 
     * @covers \Bynder\Api\Impl\OAuth2\RequestHandler::getAccessToken()
     * @throws \InvalidArgumentException
     */
    public function testGetAccessTokenError()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->bynderClient->getAccessToken(null);
    }

    /**
     * Sets up a requestHandler based on a mocked up oauthProvider.
     * 
     * @param string $grantType denotes the grant type used
     * @param string $redirectUri (optional) denoted an optional redirectUri.
     * This is only needed when using authorization_code as grant_type.
     * 
     * @return RequestHandler An instance of the requestHandler.
     */
    private function setUpRequestHandler($grantType, $redirectUri = null, $options = [])
    {
        $oauthProvider = $this->getMockBuilder('Bynder\Api\Impl\OAuth2\BynderOauthProvider')
        ->disableOriginalConstructor()
            ->setMethods(array('getAccessToken'))
            ->getMock();

        $oauthProvider
            ->expects($this->at(0))
            ->method('getAccessToken')
            ->with($grantType, $options)
            ->will($this->returnValue(self::getAccessTokenResponse()));

        $configuration = new OAuth2\Configuration(
            self::BYNDER_DOMAIN,
            $redirectUri,
            self::CLIENT_ID,
            self::CLIENT_SECRET,
            null
        );

        $requestHandler = new RequestHandler($configuration);
        $requestHandler->setOAuthProvider($oauthProvider);

        return $requestHandler;
    }

    /**
     * Helps test the returned access token response.
     * 
     * @param RequestHandler $requestHandler to be used to make requests
     */
    private function getAccessToken($requestHandler)
    {
        $tokenResponse = $requestHandler->getAccessToken(self::CODE);
        $this->assertNotNull($tokenResponse);
        $this->assertEquals($tokenResponse, self::getAccessTokenResponse());
        $this->assertEquals($tokenResponse['accessToken'], self::ACCESS_TOKEN);
    }

    /**
     * Returns a valid access token response.
     * 
     * @return array containing the token response.
     */
    private static function getAccessTokenResponse()
    {
        return [
            "accessToken" => self::ACCESS_TOKEN,
            "expires" => 1610640069,
            "refreshToken" => 'refresh-token',
            "resourceOwnerId" => "owner",
            "values" => [
                "token_type" => "bearer",
                "scope" => "offline asset:read asset:write"
            ]

        ];
    }
}
