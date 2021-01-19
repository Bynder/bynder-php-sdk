<?php

namespace Bynder\Test\AssetBank;

use Bynder\Api\Impl\Upload\FileUploader;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7;
use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

class FileUploaderTest extends TestCase
{
    private $root;

    const CHUNK_SIZE = 1024 * 1024 * 5;

    const fileId = 'testing-file_id';

    const brandId = 'testBrandId';

    const fileName = 'Testing_fileName';

    /**
     * Sets up VFS root directory.
     */
    protected function setUp()
    {
        $this->root = vfsStream::setup("root");
    }

    /** 
     * Intialize the mock request handler.
     * @return null|\PHPUnit_Framework_MockObject_MockObject
     */
    private function initMockRequestHandler()
    {
        $mockCredentials = $this->getMockBuilder('Bynder\Api\Impl\OAuth2\Configuration')
        ->disableOriginalConstructor()
            ->getMock();
        $mockRequestHandler = $this->getMockBuilder('Bynder\Api\Impl\OAuth2\RequestHandler')
        ->setConstructorArgs([$mockCredentials])
            ->disableOriginalConstructor()
            ->getMock();

        return $mockRequestHandler;
    }

    /**
     * Generates the file path by creating a test file.
     * @return string path of the file generated.
     */
    private function setUpFilePath()
    {
        $filePath = vfsStream::url('root/tempFile.txt');
        $this->assertFalse(file_exists($filePath));
        file_put_contents($filePath, "test content in file");
        $this->assertEquals(filesize($filePath), 20);
        $this->assertTrue(file_exists($filePath));
        return $filePath;
    }

    /**
     * Sets up the mock requests for implicit calls to prepareFile, uploadInChunks and finalizeFile.
     */
    private function setUpMockPrepareUploadFinalise($mockOauthHandler, $filePath)
    {
        $fileChunk = '';
        if ($file = fopen($filePath, 'rb')) {
            $fileChunk = fread($file, self::CHUNK_SIZE);
        }

        // Prepare upload.
        $mockOauthHandler
            ->expects($this->at(0))
            ->method('sendRequestAsync')
            ->with(...self::getPrepareRequest())
            ->will($this->returnValue(self::getPrepareResponse()));

        // Upload in chunks.
        $mockOauthHandler
            ->expects($this->at(1))
            ->method('sendRequestAsync')
            ->with(...self::getUploadChunksRequest(self::fileId, 0, $fileChunk))
            ->will($this->returnValue(self::getUploadChunksResponse()));

        // Finalises the upload.
        $mockOauthHandler
            ->expects($this->at(2))
            ->method('sendRequestAsync')
            ->with(...self::getFinaliseApiRequest(
                self::fileId,
                basename($filePath),
                filesize($filePath),
                1.0,
                hash("sha256", $fileChunk)
            ))
            ->will($this->returnValue(self::getFinaliseApiResponse()));
    }
    /**
     * Tests if the correct upload sequence is processed when we start a file upload.
     *
     * The order it tests is:
     *      1. Prepare upload
     *      2. Upload file in chunks
     *      3. Finalise the upload 
     *      4. Save media asset
     *
     * @covers \Bynder\Api\Impl\Upload\FileUploader::uploadFile()
     * @throws Exception
     */
    public function testCorrectUploadFileSequence()
    {

        $filePath = $this->setUpFilePath();
        // Initiate the mock handlers for normal Oauth and AWS calls.
        $mockOauthHandler = $this->initMockRequestHandler();
        $this->setUpMockPrepareUploadFinalise($mockOauthHandler, $filePath);

        // Save new media asset.
        $mockOauthHandler
            ->expects($this->at(3))
            ->method('sendRequestAsync')
            ->with(...self::getSaveMediaRequest(self::fileId, self::brandId, self::fileName))
            ->will($this->returnValue(new FulfilledPromise('DONE')));

        // Start a new FileUploader instance with our mockHandlers.
        $fileUploader = new FileUploader($mockOauthHandler);
        $fileUpload = $fileUploader->uploadFile($filePath, array(
            'brandId' => self::brandId, 'name' => self::fileName
        ));

        $this->assertNotNull($fileUpload);
        $this->assertEquals($fileUpload, 'DONE');
    }

    /**
     * Tests if the correct upload sequence is processed when we start a file upload,
     *  with a mediaId specified.
     *
     * The order it tests is:
     *      1. Prepare upload
     *      2. Upload file in chunks
     *      3. Finalise the upload 
     *      4. Save media asset
     *
     * @covers \Bynder\Api\Impl\Upload\FileUploader::uploadFile()
     * @throws Exception
     */
    public function testCorrectUploadFileSequenceMediaId()
    {
        $filePath = $this->setUpFilePath();
        // Initiate the mock handlers for normal Oauth and AWS calls.
        $mockOauthHandler = $this->initMockRequestHandler();
        $mediaId = 'test-media-id';
        $this->setUpMockPrepareUploadFinalise($mockOauthHandler, $filePath);

        // Save existing media asset.
        $mockOauthHandler
            ->expects($this->at(3))
            ->method('sendRequestAsync')
            ->with(...self::getSaveMediaRequestWithMediaId(self::fileId, $mediaId))
            ->will($this->returnValue(new FulfilledPromise('DONE')));

        // Start a new FileUploader instance with our mockHandlers.
        $fileUploader = new FileUploader($mockOauthHandler);
        $fileUpload = $fileUploader->uploadFile($filePath, array(
            'mediaId' => $mediaId
        ));

        $this->assertNotNull($fileUpload);
        $this->assertEquals($fileUpload,  'DONE');
    }

    /**
     * Tests if the exception is thrown under FileUploader::saveMediaAsync,
     *  when a invalid brandId is specified.
     *
     * The order it tests is:
     *      1. Prepare upload
     *      2. Upload file in chunks
     *      3. Finalise the upload 
     *      4. Save media asset
     *
     * @covers \Bynder\Api\Impl\Upload\FileUploader::uploadFile()
     * @throws Exception
     */
    public function testInvalidBrandId()
    {
        $filePath = $this->setUpFilePath();
        // Initiate the mock handlers for normal Oauth and AWS calls.
        $mockOauthHandler = $this->initMockRequestHandler();
        $brandId = '';
        $this->setUpMockPrepareUploadFinalise($mockOauthHandler, $filePath);

        // Start a new FileUploader instance with our mockHandlers.
        $fileUploader = new FileUploader($mockOauthHandler);
        $fileUpload = $fileUploader->uploadFile($filePath, array(
            'brandId' => $brandId
        ));
        $this->assertEquals($fileUpload, json_encode(
            array(
                "Error" => "Unable to upload file. Invalid or Empty brandId"
            )
        ));
    }

    /**
     * Builds a valid prepare upload request.
     *
     * @return array The request params.
     */
    private static function getPrepareRequest()
    {
        return [
            'POST',
            'v7/file_cmds/upload/prepare'
        ];
    }

    /**
     * Returns a Fullfilled promise with a fake fileId.
     *
     * @return array The request params.
     */
    private static function getPrepareResponse()
    {
        return new FulfilledPromise(
            [
                'file_id' => 'testing-file_id'
            ]
        );
    }

    /**
     * Builds a valid init upload request.
     *
     * @param string Path of the file to be uploaded.
     * @param integer The chunk number of is upload.
     * @param string The data chunk to be uploaded.
     * @return array The request params.
     */
    private static function getUploadChunksRequest($fileId, $chunkNumber, $chunk)
    {
        return [
            'POST',
            'v7/file_cmds/upload/' . $fileId . '/chunk/' . $chunkNumber,
            ['headers' => [
                'content-sha256' => hash("sha256", $chunk)
            ]]
        ];
    }

    /**
     * Returns a valid fulfilled response with dummy data for the upload request.
     *
     * @return FulfilledPromise
     */
    private static function getUploadChunksResponse()
    {
        return new FulfilledPromise(
            [
                'test' => 'SuccessfulResponseUploadChunk'
            ]
        );
    }

    /**
     * Builds a valid finalise api request.
     *
     * @param string $fileId of the file to be uploaded returned by the prepare.
     * @param string $fileName of the file to be uploaded.
     * @param integer $fileSize of the file to be uploaded.
     * @param integer $chunksCount denoting number of chunks in which the file is to be uploaded.
     * @param string $fileSha256 digest of the file is to be uploaded.
     * 
     * @return array The request params.
     */
    private static function getFinaliseApiRequest($fileId, $fileName, $fileSize, $chunksCount, $fileSha256)
    {
        return [
            'POST',
            'v7/file_cmds/upload/' . $fileId . '/finalise_api',
            [
                'form_params' => [
                    'fileName' => $fileName,
                    'fileSize' => $fileSize,
                    'chunksCount' => $chunksCount,
                    'sha256' => $fileSha256
                ]
            ]
        ];
    }

    /**
     * Returns a fulfilled promise with the correlationId in the header for the finalise_api request.
     *
     * @return FulfilledPromise
     */
    private static function getFinaliseApiResponse()
    {

        return new FulfilledPromise(
            new Psr7\Response(200, ['X-API-Correlation-ID' => 'TesterCorrelationId'], 'body string')
        );
    }

    /**
     * Builds a valid save media request.
     *
     * @param string $fileId of the file to be uploaded returned by the prepare.
     * @param string $brandId of the file to be uploaded.
     * @param string $fileName of the file to be uploaded.
     *
     * @return array The request params.
     */
    private static function getSaveMediaRequest($fileId, $brandId, $fileName)
    {
        return [
            'POST',
            'api/v4/media/save/' . $fileId,
            [
                'form_params' => [
                    'brandId' => $brandId,
                    'name' => $fileName
                ]
            ]
        ];
    }

    /**
     * Builds a valid save media request when mediaId is passed.
     *
     * @param string $fileId of the file to be uploaded returned by the prepare.
     * @param string $mediaId of the file to be updated with the new version.
     *
     * @return array The request params.
     */
    private static function getSaveMediaRequestWithMediaId($fileId, $mediaId)
    {
        return [
            'POST',
            'api/v4/media/' . $mediaId . "/save/" . $fileId
        ];
    }
}
