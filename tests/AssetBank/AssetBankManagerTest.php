<?php
namespace Bynder\Test\AssetBank;

use Bynder\Api\Impl\AssetBankManager;
use PHPUnit\Framework\TestCase;

class AssetBankManagerTest extends TestCase
{

    /**
     * Test if we call getBrands it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getBrands()
     */
    public function testGetBrands()
    {
        $returnedBrands = array();
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $stub->expects($this->once())
            ->method('sendRequestAsync')
            ->with('GET', 'api/v4/brands/')
            ->willReturn(array());

        $assetBankManager = new AssetBankManager($stub);
        $brands = $assetBankManager->getBrands();

        self::assertNotNull($brands);
        self::assertEquals($brands, $returnedBrands);
    }

    /**
     * Test if we call getMediaList it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getMediaList()
     */
    public function testGetMediaList()
    {
        $returnedMedia = array();
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/media/')
            ->willReturn($returnedMedia);

        $assetBankManager = new AssetBankManager($stub);
        $mediaList = $assetBankManager->getMediaList();

        self::assertNotNull($mediaList);
        self::assertEquals($mediaList, $returnedMedia);

        // Test with query params.
        $query = array(
            'count' => true,
            'limit' => 2,
            'type' => 'image'
        );
        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/media/', array('query' => $query))
            ->willReturn($returnedMedia);

        $assetBankManager = new AssetBankManager($stub);
        $mediaList = $assetBankManager->getMediaList($query);

        self::assertNotNull($mediaList);
        self::assertEquals($mediaList, $returnedMedia);
    }

    /**
     * Test if we call getMediaInfo it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getMediaInfo()
     */
    public function testGetMediaInfo()
    {
        $returnedMedia = array();
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/media/1111/')
            ->willReturn($returnedMedia);

        $assetBankManager = new AssetBankManager($stub);
        $mediaInfo = $assetBankManager->getMediaInfo('1111');

        self::assertNotNull($mediaInfo);
        self::assertEquals($mediaInfo, $returnedMedia);

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/media/1111/')
            ->willReturn($returnedMedia);

        $assetBankManager = new AssetBankManager($stub);
        $mediaInfo = $assetBankManager->getMediaInfo('1111');

        self::assertNotNull($mediaInfo);
        self::assertEquals($mediaInfo, $returnedMedia);
    }

    /**
     * Test if we call getMetaproperties it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getMetaproperties()
     */
    public function testGetMetaproperties()
    {
        $returnedMedia = array();
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/metaproperties/')
            ->willReturn($returnedMedia);

        $assetBankManager = new AssetBankManager($stub);
        $metaproperties = $assetBankManager->getMetaproperties();

        self::assertNotNull($metaproperties);
        self::assertEquals($metaproperties, $returnedMedia);

        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/metaproperties/', array(
                'query' => array('count' => 1)
                )
            )
            ->willReturn(array('query'));

        $assetBankManager = new AssetBankManager($stub);
        $metaproperties = $assetBankManager->getMetaproperties(array('count' => 1));

        self::assertNotNull($metaproperties);
        self::assertEquals($metaproperties, array('query'));
    }

    /**
     * Test if we call getTags it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getTags()
     */
    public function testGetTags()
    {
        $returnedMedia = array();
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/tags/')
            ->willReturn($returnedMedia);

        $assetBankManager = new AssetBankManager($stub);
        $tagList = $assetBankManager->getTags();

        self::assertNotNull($tagList);
        self::assertEquals($tagList, $returnedMedia);

        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/tags/', array(
                'query' => array('limit' => 10)
                )
            )
            ->willReturn(array('query'));

        $assetBankManager = new AssetBankManager($stub);
        $tagList = $assetBankManager->getTags(array('limit' => 10));

        self::assertNotNull($tagList);
        self::assertEquals($tagList, array('query'));
    }

    /**
     * Test if we call getCategories it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getTags()
     */
    public function testGetCategories()
    {
        $returnedMedia = array();
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/categories')
            ->willReturn($returnedMedia);

        $assetBankManager = new AssetBankManager($stub);
        $categoryList = $assetBankManager->getCategories();

        self::assertNotNull($categoryList);
        self::assertEquals($categoryList, $returnedMedia);
    }

    /**
     * Test if we call modifyMedia it will use the correct params for the request and returns successfully.
     * HINT: it is rather skeleton, to use it properly this test requires much more complex mock with configured asset
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::modifyMedia()
     */
    public function testModifyMedia()
    {
        $return = array();
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $mediaId = 1111;
        $formData = ['name' => 'test'];

        $stub->method('sendRequestAsync')
            ->with('POST', 'api/v4/media/'.$mediaId.'/', ['form_params' => $formData])
            ->willReturn($return);

        $assetBankManager = new AssetBankManager($stub);
        $modifyMediaReturn = $assetBankManager->modifyMedia($mediaId, $formData);

        self::assertNotNull($modifyMediaReturn);
        self::assertEquals($modifyMediaReturn, $return);
    }


    /**
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getDerivatives()
     */
    public function testGetDerivatives()
    {
        $returnedMedia = array();
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/account/derivatives/')
            ->willReturn($returnedMedia);

        $assetBankManager = new AssetBankManager($stub);
        $derivativesList = $assetBankManager->getDerivatives();

        self::assertNotNull($derivativesList);
        self::assertEquals($derivativesList, $returnedMedia);
    }

    /**
     * Test if we call getMediaDownloadLocation it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getMediaDownloadLocation()
     */
    public function testGetMediaDownloadLocation()
    {
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $mediaId = 1111;
        $type = 'original';

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/media/' . $mediaId . '/download/', array(
                    'query' => array(
                        'type' => $type
                    )
                )
            )
            ->willReturn(array('query'));

        $assetBankManager = new AssetBankManager($stub);
        $mediaLocation = $assetBankManager->getMediaDownloadLocation($mediaId);

        self::assertNotNull($mediaLocation);
        self::assertEquals($mediaLocation, array('query'));
    }

    /**
     * Test if we call getMediaDownloadLocationByVersion it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getMediaDownloadLocation()
     */
    public function testGetMediaDownloadLocationByVersion()
    {
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $mediaId = 1111;
        $version = 3;

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/media/' . $mediaId . '/' . $version . '/download/')
            ->willReturn(array());

        $assetBankManager = new AssetBankManager($stub);
        $mediaLocation = $assetBankManager->getMediaDownloadLocationByVersion($mediaId, $version);

        self::assertNotNull($mediaLocation);
        self::assertEquals($mediaLocation, array());
    }

    /**
     * Test if we call getMediaDownloadLocationForAssetItem it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getMediaDownloadLocation()
     */
    public function testGetMediaDownloadLocationForAssetItem()
    {
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $mediaId = 1111;
        $itemId = 2222;
        $hash = false;

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/media/' . $mediaId . '/download/' . $itemId . '/', array(
                    'query' => array(
                        'hash' => $hash
                    )
                )
            )
            ->willReturn(array('query'));

        $assetBankManager = new AssetBankManager($stub);
        $mediaLocation = $assetBankManager->getMediaDownloadLocationForAssetItem($mediaId, $itemId);

        self::assertNotNull($mediaLocation);
        self::assertEquals($mediaLocation, array('query'));
    }

    /**
     * Test if we call getMetaproperty it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getMetaproperty()
     */
    public function testGetMetapropery()
    {
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $propertyId = '00000000-0000-0000-0000000000000000';

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/metaproperties/' . $propertyId . '/')
            ->willReturn(array());

        $assetBankManager = new AssetBankManager($stub);
        $mediaLocation = $assetBankManager->getMetaproperty($propertyId);

        self::assertNotNull($mediaLocation);
        self::assertEquals($mediaLocation, array());
    }

    /**
     * Test if we call getMetapropertyDependencies it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getMetapropertyDependencies()
     */
    public function testGetMetapropertyDependencies()
    {
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $propertyId = '00000000-0000-0000-0000000000000000';

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/metaproperties/' . $propertyId . '/dependencies/')
            ->willReturn(array());

        $assetBankManager = new AssetBankManager($stub);
        $mediaLocation = $assetBankManager->getMetapropertyDependencies($propertyId);

        self::assertNotNull($mediaLocation);
        self::assertEquals($mediaLocation, array());
    }

    /**
     * Test if we call getMetapropertyOptions it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getMetapropertyOptions()
     */
    public function testGetMetapropertyOptions()
    {
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $optionId = '00000000-0000-0000-0000000000000000';
        $query = array('ids' => $optionId);

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/metaproperties/options/', array(
                    'query' => $query
                ))
            ->willReturn(array('query'));

        $assetBankManager = new AssetBankManager($stub);
        $result = $assetBankManager->getMetapropertyOptions($query);

        self::assertNotNull($result);
        self::assertEquals($result, array('query'));
    }

    /**
     * Test if we call getMetapropetryGlobalOptionDependencies it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getMetapropetryGlobalOptionDependencies()
     */
    public function testGetMetapropetryGlobalOptionDependencies()
    {
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/metaproperties/options/dependencies/')
            ->willReturn(array());

        $assetBankManager = new AssetBankManager($stub);
        $result = $assetBankManager->getMetapropetryGlobalOptionDependencies();

        self::assertNotNull($result);
        self::assertEquals($result, array());
    }

    /**
     * Test if we call getMetapropertyOptionDependencies it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getMetapropertyOptionDependencies()
     */
    public function testGetMetapropertyOptionDependencies()
    {
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $propertyId = '00000000-0000-0000-0000000000000000';

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/metaproperties/' . $propertyId . '/options/dependencies/')
            ->willReturn(array());

        $assetBankManager = new AssetBankManager($stub);
        $result = $assetBankManager->getMetapropertyOptionDependencies($propertyId);

        self::assertNotNull($result);
        self::assertEquals($result, array());
    }

    /**
     * Test if we call getMetapropertySpecificOptionDependencies it will use the correct params for the request and returns successfully.
     *
     * @covers \Bynder\Api\Impl\AssetBankManager::getMetapropertySpecificOptionDependencies()
     */
    public function testGetMetapropertySpecificOptionDependencies()
    {
        $stub = $this->getMockBuilder('Bynder\Api\Impl\Oauth\IOauthRequestHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $propertyId = '00000000-0000-0000-0000000000000000';
        $optionId = '00000000-0000-0000-0000000000000000';

        $stub->method('sendRequestAsync')
            ->with('GET', 'api/v4/metaproperties/' . $propertyId . '/options/' . $optionId . '/dependencies/', array(
                'query' => array('includeGroupedResults' => false)
            ))
            ->willReturn(array());

        $assetBankManager = new AssetBankManager($stub);
        $result = $assetBankManager->getMetapropertySpecificOptionDependencies($propertyId, $optionId, array('includeGroupedResults' => false));

        self::assertNotNull($result);
        self::assertEquals($result, array());
    }
}
