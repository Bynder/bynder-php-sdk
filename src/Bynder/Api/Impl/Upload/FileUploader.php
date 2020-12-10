<?php

/**
 *
 * Copyright (c) Bynder. All rights reserved.
 *
 * Licensed under the MIT License. For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bynder\Api\Impl\Upload;

use Exception;
use GuzzleHttp\Promise;
use VirtualFileSystem\FileSystem;
use Bynder\Api\Impl\AbstractRequestHandler;

/**
 * Class used to upload files to Bynder.
 */
class FileUploader
{
    /**
     * Max chunk size
     */
    const CHUNK_SIZE = 1024 * 1024 * 5;

    /**
     *
     * @var AbstractRequestHandler Request handler used to communicate with the API.
     */
    private $requestHandler;

    /**
     *
     * @var string sh256 digest of the file to be uploaded.
     */
    private $fileSha256;

    /**
     * Initialises a new instance of the class.
     *
     * @param AbstractRequestHandler $requestHandler Request handler used to communicate with the API.
     */
    public function __construct(AbstractRequestHandler $requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }

    /**
     * Creates a new instance of FileUploader.
     *
     * @param AbstractRequestHandler $requestHandler Request handler used to communicate with the API.
     * @return FileUploader
     */
    public static function create(AbstractRequestHandler $requestHandler)
    {
        return new FileUploader($requestHandler);
    }

    /**
     * Uploads a file with the data specified in the data parameter.
     *
     * Client requests S3-upload endpoint information from the Bynder API.
     * For each file the client needs to requests upload authorization from the Bynder API.
     * The client uploads a file chunked with CORS directly to the Amazon S3 endpoint received in step 1.
     *      Each chunk is named "FIXED_PREFIX/p{PARTNUMBER}", the partnumber needs to be sequentially updated.
     * Each chunk needs to be registered as completed using a request to Bynder.
     * When the file is completely uploaded, the client sends a “finalise” request to Bynder.
     * After the file is processed, the client sends a “save” call to save the file in Bynder.
     *      Additional information can be provided such as title, tags, metadata and description.
     *
     * @param $data array containing the file and media asset information.
     *
     * @return Promise\Promise file promise.
     * @throws Exception
     */
    public function uploadFile($data)
    {
        try {
            $fileId = $this->prepareFile()->wait()['file_id'];
            $filePath = $data['filePath'];
            $fileSize = filesize($filePath);
            $this->fileSha256 = hash_file("sha256", $filePath);
            $chunksCount = $this->uploadInChunks($filePath, $fileId, $fileSize);
            $correlationId = $this->finalizeFile($fileId, $filePath, $fileSize, $chunksCount);
            $media = $this->saveMediaAsync($fileId, $data)->wait();
            return json_encode(array(
                'fileId' => $fileId, 'correlationId' => $correlationId,
                'media' => $media
            ));
        } catch (Exception $e) {
            echo "Unable to upload file. " . $e->getMessage();
        }
    }

    private function prepareFile()
    {
        return $this->requestHandler->sendRequestAsync('POST', 'v7/file_cmds/upload/prepare');
    }

    private function uploadInChunks($filePath, $fileId, $fileSize)
    {
        $chunksCount = 0;
        if ($file = fopen($filePath, 'rb')) {
            $chunksCount = round(($fileSize + self::CHUNK_SIZE - 1) / self::CHUNK_SIZE);
            $chunkNumber = 0;
            while ($chunk = fread($file, self::CHUNK_SIZE)) {
                $chunkNumber++;
                echo $chunk;
                // POST the chunk here
                $this->uploadChunk($fileId, $chunk, $chunkNumber);
            }
        }
        return $chunksCount;
    }

    private function uploadChunk($fileId, $chunk, $chunkNumber)
    {
        $sessionHeader = ['headers' => [
            'content-sha256' => hash("sha256", $chunk)
        ]];
        $res = $this->requestHandler->sendRequestAsync(
            'POST',
            'v7/file_cmds/upload/' . $fileId . '/chunk/' . $chunkNumber,
            $sessionHeader
        )->wait();
    }

    private function finalizeFile($fileId, $filePath, $fileSize, $chunksCount)
    {

        $formData = array(
            'fileName' => basename($filePath),
            'fileSize' => $fileSize,
            'chunksCount' => $chunksCount,
            'sha256' => $this->fileSha256,
            'intent' => "upload_main_uploader_asset",
        );

        $response = $this->requestHandler->sendRequestAsync(
            'POST',
            'v7/file_cmds/upload/' . $fileId . '/finalise_api',
            [
                'form_params' => $formData
            ]
        )->wait();
        return $response->getHeader('X-API-Correlation-ID')[0];
    }


    /**
     * Saves the file in the Bynder Asset Bank. This can be either a new or existing file, depending on whether or not
     * the mediaId parameter is passed.
     *
     * @param array $data Array of relevant file upload data, such as uploadId and brandId.
     *
     * @return Promise\Promise The information of the uploaded file, including IDs and all final file urls.
     * @throws Exception
     */
    public function saveMediaAsync($fileId, $data)
    {
        $uri = "api/v4/media/save/" . $fileId;
        if (isset($data['mediaId'])) {
            $uri = sprintf("api/v4/media/" . $data['mediaId'] . "/save/" . $fileId);
            unset($data['mediaId']);
        }
        return $this->requestHandler->sendRequestAsync('POST', $uri, ['form_params' => $data]);
    }
}
