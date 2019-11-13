<?php
namespace Bynder\Test\AssetBank;

use Bynder\Api\Impl\Upload\FileUploader;
use GuzzleHttp\Promise\FulfilledPromise;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class FileUploaderTest extends TestCase
{
    private $root;

    /**
     * Sets up VFS root directory.
     */
    protected function setUp()
    {
        $this->root = vfsStream::setup("root");
    }

    /**
     * @param $type string The handler to
     * @return null|\PHPUnit_Framework_MockObject_MockObject
     */
    private function initMockRequestHandler($type)
    {
        $mockRequestHandler = null;
        switch ($type) {
            case 'oauth':
                $mockCredentials = $this->getMockBuilder('Bynder\Api\Impl\OAuth2\Configuration')
                    ->disableOriginalConstructor()
                    ->getMock();
                $mockRequestHandler = $this->getMockBuilder('Bynder\Api\Impl\OAuth2\RequestHandler')
                    ->setConstructorArgs([$mockCredentials])
                    ->getMock();
                break;

            case 'aws':
                $mockRequestHandler = $this->getMockBuilder('Bynder\Api\Impl\Upload\AmazonApi')
                    ->disableOriginalConstructor()
                    ->getMock();
                break;
        }
        return $mockRequestHandler;
    }

    /**
     * Tests if the correct upload sequence is processed when we start a file upload.
     *
     * The order it tests is:
     *      1. Init upload
     *      2. Get closest s3 endpoint
     *      3. Upload part to Amazon
     *      4. Register chunk in Bynder
     *      5. Finalize upload.
     *      6. Poll status
     *      7. Save
     *
     * @covers \Bynder\Api\Impl\Upload\FileUploader::uploadFile()
     * @throws \Exception
     */
    public function testCorrectUploadFileSequence()
    {
        $filePath = vfsStream::url('root/tempFile.txt');
        $this->assertFalse(file_exists($filePath));
        file_put_contents($filePath, "test content in file");
        $this->assertTrue(file_exists($filePath));

        // Initiate the mock handlers for normal Oauth and AWS calls.
        $mockOauthHandler = $this->initMockRequestHandler('oauth');
        $mockAwsHandler = $this->initMockRequestHandler('aws');

        // Get closest upload endpoint.
        $mockOauthHandler
            ->expects($this->at(0))
            ->method('sendRequestAsync')
            ->with(...self::getUploadEndpointRequest())
            ->will($this->returnValue(self::getUploadEndpointResponse()));

        // Initialise upload.
        $mockOauthHandler
            ->expects($this->at(1))
            ->method('sendRequestAsync')
            ->with(...self::getInitUploadRequest($filePath))
            ->will($this->returnValue(self::getInitUploadResponse()));

        // Upload chunk to Amazon.
        $mockAwsHandler
            ->expects($this->once())
            ->method('uploadPartToAmazon')
            ->will($this->returnValue(new FulfilledPromise('uploadPartToAmazonCompleted')));

        // Register chunk in Bynder.
        $mockOauthHandler
            ->expects($this->at(2))
            ->method('sendRequestAsync')
            ->with(...self::getRegisterChunkRequest())
            ->will($this->returnValue(self::getRegisterChunkResponse()));

        // Finalises the upload.
        $mockOauthHandler
            ->expects($this->at(3))
            ->method('sendRequestAsync')
            ->with(...self::getFinaliseRequest())
            ->will($this->returnValue(self::getFinaliseResponse()));

        // Poll status of upload.
        $mockOauthHandler
            ->expects($this->at(4))
            ->method('sendRequestAsync')
            ->with(...self::getPollStatusRequest())
            ->will($this->returnValue(self::getPollStatusResponse()));

        // Save media asset.
        $mockOauthHandler
            ->expects($this->at(5))
            ->method('sendRequestAsync')
            ->with(...self::getSaveMediaRequest($filePath))
            ->will($this->returnValue(new FulfilledPromise('DONE')));

        // Start a new FileUploader instance with our mockHandlers.
        $fileUploader = new FileUploader($mockOauthHandler, $mockAwsHandler);
        $fileUpload = $fileUploader->uploadFile(array(
            'filePath' => $filePath
        ));

        $fileUploadRes = $fileUpload->wait();

        $this->assertNotNull($fileUpload);
        $this->assertEquals($fileUploadRes, 'DONE');
    }

    /**
     * Builds a valid init upload request.
     *
     * @param $filePath string Path of the file to be uploaded.
     * @return array The request params.
     */
    private static function getInitUploadRequest($filePath)
    {
        return [
            'POST',
            'api/upload/init',
            ['form_params' => ['filename' => $filePath]]
        ];
    }

    /**
     * Gets a valid Fullfilled promise with the necessary response variables.
     *
     * @return FulfilledPromise
     */
    private static function getInitUploadResponse()
    {
        return new FulfilledPromise(
            [
                's3_filename' => 's3_filename',
                's3file' => ['uploadid' => 'fakeUploadId', 'targetid' => 'fakeTargetid'],
            ]
        );
    }

    /**
     * Builds a valid upload endpoint request.
     *
     * @return array The request params.
     */
    private static function getUploadEndpointRequest()
    {
        return ['GET', 'api/upload/endpoint'];
    }

    /**
     * Returns a Fullfilled promise with some dummy data.
     *
     * @return FulfilledPromise
     */
    private static function getUploadEndpointResponse()
    {
        return new FulfilledPromise('fakeUploadLocationEndpoint');
    }

    /**
     * Builds a valid register chunk request.
     *
     * @return array The request params.
     */
    private static function getRegisterChunkRequest()
    {
        return [
            'POST',
            'api/v4/upload/fakeUploadId/',
            [
                'form_params' => [
                    'id' => 'fakeUploadId',
                    'targetid' => 'fakeTargetid',
                    'filename' => 's3_filename/p1',
                    'chunkNumber' => 1,
                ]
            ]
        ];
    }

    /**
     * Returns a Fullfilled promise with a fake UploadId.
     *
     * @return FulfilledPromise
     */
    private static function getRegisterChunkResponse()
    {
        return new FulfilledPromise('fakeUploadId');
    }

    /**
     * Builds a valid finalise upload request.
     *
     * @return array The request params.
     */
    private static function getFinaliseRequest()
    {
        return [
            'POST',
            'api/v4/upload/fakeUploadId/',
            [
                'form_params' => [
                    'id' => 'fakeUploadId',
                    'targetid' => 'fakeTargetid',
                    's3_filename' => 's3_filename/p1',
                    'chunks' => 1,
                ]
            ]
        ];
    }

    /**
     * Returns a Fullfilled promise with a fake importId.
     *
     * @return FulfilledPromise
     */
    private static function getFinaliseResponse()
    {
        return new FulfilledPromise(['importId' => 'importId']);
    }

    /**
     * Builds a valid poll status request.
     *
     * @return array The request params.
     */
    private static function getPollStatusRequest()
    {
        return ['GET', 'api/v4/upload/poll/', ['query' => ['items' => 'importId'], 'delay' => 0]];
    }

    /**
     * Returns a Fullfilled promise the required response params.
     *
     * @return FulfilledPromise
     */
    private static function getPollStatusResponse()
    {
        return new FulfilledPromise(
            [
                'itemsDone' => 'importId',
                'itemsFailed' => null,
            ]);
    }

    /**
     * Builds a valid save media request.
     *
     * @param string $filePath The file to be uploaded
     * @return array The request params.
     */
    private static function getSaveMediaRequest($filePath)
    {
        return [
            'POST',
            'api/v4/media/save/',
            [
                'form_params' => [
                    'filePath' => $filePath,
                    'importId' => 'importId'
                ]
            ]
        ];
    }
}
