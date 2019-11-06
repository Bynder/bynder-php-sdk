<?php
namespace Bynder\Test\Bynder\Api;

use PHPUnit\Framework\TestCase;
use Bynder\Api\BynderClient;
use Bynder\Api\Impl\OAuth2;
use Bynder\Api\Impl\PermanentTokens;

class BynderClientTest extends TestCase
{
    public function setUp()
    {
        $this->configuration = new OAuth2\Configuration(
            'test.getbynder.com',
            'test.com/callback',
            'clientId',
            'clientSecret',
            null
        );
        $this->bynderClient = new BynderClient($this->configuration);
    }

    public function testAllowedConfigurationOptions()
    {
        self::assertInstanceOf(
            'Bynder\Api\BynderClient',
            new BynderClient(new PermanentTokens\Configuration('', ''))
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
}
