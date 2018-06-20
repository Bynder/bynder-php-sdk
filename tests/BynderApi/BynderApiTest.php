<?php

namespace Bynder\Test\BynderApi;

use Bynder\Api\BynderApiFactory;
use Bynder\Api\Impl\BynderApi;
use Bynder\Api\Impl\Oauth\OauthRequestHandler;
use PHPUnit\Framework\TestCase;

/**
 * Test Class for BynderApi class
 */
class BynderApiTest extends TestCase
{

    /**
     * @covers \Bynder\Api\Impl\BynderApiFactory::create
     */
    public function testCreateApiFactory()
    {
        $bynderApiFactory = new BynderApiFactory();
        self::assertNotNull($bynderApiFactory);

        return $bynderApiFactory;
    }

    /**
     * @param $bynderApiFactory BynderApiFactory
     *
     * @covers  \Bynder\Api\Impl\BynderApi::create
     * @depends testCreateApiFactory
     * @return BynderApi
     */
    public function testCreateApiService(BynderApiFactory $bynderApiFactory)
    {
        $settings = array(
            'consumerKey' => '11111111',
            'consumerSecret' => '11111111',
            'token' => '11111111',
            'tokenSecret' => '11111111',
            'baseUrl' => 'testUrl'
        );

        $bynderApi = $bynderApiFactory->create($settings);
        self::assertNotNull($bynderApi);

        return $bynderApi;
    }

    /**
     * Tests creation of BynderApi with invalid settings
     *
     * @covers  \Bynder\Api\Impl\BynderApi::create
     * @depends testCreateApiFactory
     *
     * @param BynderApiFactory $bynderApiFactory
     *
     * @return BynderApi
     */
    public function testCreateApiServiceFail(BynderApiFactory $bynderApiFactory)
    {
        self::expectException("InvalidArgumentException");
        $bynderApi = $bynderApiFactory->create(null);

        return $bynderApi;
    }

    /**
     * @covers  \Bynder\Api\Impl\BynderApi::getAssetBankManager
     * @depends testCreateApiService
     *
     * @param BynderApi $bynderApi
     */
    public function testGetAssetBankManager(BynderApi $bynderApi)
    {
        $assetBankManager = $bynderApi->getAssetBankManager();
        self::assertNotNull($assetBankManager);
    }

    /**
     * @covers  \Bynder\Api\Impl\BynderApi::getRequestHandler()
     * @depends testCreateApiService
     *
     * @param BynderApi $bynderApi
     */
    public function testGetRequestHandler(BynderApi $bynderApi)
    {
        self::assertInstanceOf(OauthRequestHandler::class, $bynderApi->getRequestHandler());
    }
}
