<?php
namespace Bynder\Test\Bynder\Api;

use PHPUnit\Framework\TestCase;
use Bynder\Api\BynderClient;
use Bynder\Api\Impl\OAuth2\RequestHandler;

use Bynder\Api\Impl\OAuth2;

class BynderClientTest extends TestCase
{
    const CODE = 'dummy-test-code';
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
            new BynderClient(new OAuth2\Configuration('', '', '', '', ''))
        );

        self::setExpectedException('\Exception');
        new BynderClient(null);
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
            $parsedUrl['host'], $this->configuration->getBynderDomain()
        );
        self::assertSame(
            $queryParams['redirect_uri'], $this->configuration->getRedirectUri()
        );
        self::assertSame(
            $queryParams['client_id'], $this->configuration->getClientId()
        );
        self::assertSame(
            $queryParams['scope'], 'openid offline'
        );
    }

    //test for authorization_code
    public function testGetAccessToken()
    {
        $requestHandler = $this->setUpOAuth2('authorization_code', 'test.com/callback');
        $this->getAccessToken($requestHandler);
    }

    // test for auth_code but no code
    public function testGetAccessTokenError()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->bynderClient->getAccessToken(null);
    }

    //test for client creds
    public function testGetAccessTokenClientCredentials()
    {
        $requestHandler = $this->setUpOAuth2('client_credentials');
        $this->getAccessToken($requestHandler);
    }


    private function setUpOAuth2($grantType, $redirectUri = null)
    {
        $oAuthProvider = $this->getMockBuilder('Bynder\Api\Impl\OAuth2\BynderOauthProvider')
            ->setMethods(array('getAccessToken'))
            ->getMock();

        $oAuthProvider
            ->expects($this->at(0))
            ->method('getAccessToken')
            ->with($grantType)
            ->will($this->returnValue(self::getPrepareResponse()));

        $configuration = new OAuth2\Configuration(
            self::BYNDER_DOMAIN,
            $redirectUri,
            self::CLIENT_ID,
            self::CLIENT_SECRET,
            null
        );

        $requestHandler = new RequestHandler($configuration);
        $requestHandler->setOAuthProvider($oAuthProvider);

        return $requestHandler;
    }

    private function getAccessToken($requestHandler)
    {
        $token = $requestHandler->getAccessToken(self::CODE);
        $this->assertNotNull($token);
        $this->assertEquals($token, self::getPrepareResponse());
    }

    private static function getPrepareResponse()
    {
        return [
            "accessToken" => "dummy-access-token",
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
