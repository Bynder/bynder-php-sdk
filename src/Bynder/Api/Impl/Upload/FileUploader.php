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
     * Max polling iterations to wait for the asset to be converted.
     */
    const MAX_POLLING_ITERATIONS = 60;

    /**
     * Idle time between iterations in milliseconds.
     */
    const POLLING_IDLE_TIME = 2000;

    /**
     * Max polling iterations to wait for the asset to be converted.
     */
    const MAX_CONCURRENT_CHUNKS = 1;

    /**
     *
     * @var AbstractRequestHandler Request handler used to communicate with the API.
     */
    private $requestHandler;

    /**
     * @var AmazonApi Amazon API used to upload parts.
     */
    private $amazonApi;

    /**
     * AWS bucket Url to upload chunks.
     */
    private $awsBucket;

    /**
     * Initialises a new instance of the class.
     *
     * @param AbstractRequestHandler $requestHandler Request handler used to communicate with the API.
     * @param AmazonApi $amazonApi AmazonApi to upload parts.
     */
    public function __construct(AbstractRequestHandler $requestHandler, AmazonApi $amazonApi)
    {
        $this->requestHandler = $requestHandler;
        $this->amazonApi = $amazonApi;
    }

    /**
     * Creates a new instance of FileUploader.
     *
     * @param AbstractRequestHandler $requestHandler Request handler used to communicate with the API.
     * @return FileUploader
     */
    public static function create(AbstractRequestHandler $requestHandler)
    {
        return new FileUploader($requestHandler, new AmazonApi());
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
        $uploadedFilePromise = $this->getClosestUploadEndpoint()
            ->then(
                function () use ($data) {
                    return $this->requestUploadInformationAsync($data['filePath']);
                })
            ->then(
                function ($uploadRequestInfo) use ($data) {
                    if ($file = fopen($data['filePath'], 'rb')) {

                        $fileSize = filesize($data['filePath']);

                        $numberOfChunks = round(($fileSize + self::CHUNK_SIZE - 1) / self::CHUNK_SIZE);
                        $chunkNumber = 0;

                        // This is where the magic happens. We create all the promises via an Iterator function.
                        $promises = $this->uploadChunkIterator($file, $data['filePath'], $uploadRequestInfo,
                            $numberOfChunks, $chunkNumber);
                        // After that we batch them all together using each_limit_all, which will guarantee all chunks have been uploaded properly.
                        $eachPromises = Promise\each_limit_all($promises, self::MAX_CONCURRENT_CHUNKS);
                        return $eachPromises->then(
                            function ($value) use ($uploadRequestInfo, $chunkNumber) {
                                return ['requestInfo' => $uploadRequestInfo, 'chunkNumber' => $chunkNumber];
                            });
                    } else {
                        throw new Exception("File not Found");
                    }
                }
            )
            ->then(
                function ($value) {
                    return $this->finalizeUploadAsync($value['requestInfo'], $value['chunkNumber']);
                }
            )
            ->then(
                function ($finalizeResponse) {
                    return $this->hasFinishedSuccessfullyAsync($finalizeResponse)
                        ->then(
                            function ($response) use ($finalizeResponse) {
                                return [
                                    'pollStatus' => $response,
                                    'finalizeData' => $finalizeResponse
                                ];
                            });
                }
            )
            ->then(
                function ($value) use ($data) {
                    if ($value['pollStatus'] != false) {
                        $data['importId'] = $value['finalizeData']['importId'];
                        return $this->saveMediaAsync($data);
                    } else {
                        throw new Exception("Converter did not finish. Upload failed.");
                    }
                }
            );

        return $uploadedFilePromise;
    }

    /**
     * Iterator function used to control how many file upload promises are sent out.
     *
     * @param $file
     * @param $filePath
     * @param $uploadRequestInfo
     * @param $numberOfChunks
     * @param $chunkNumber
     * @return \Generator A promise representing a chunk file upload.
     */
    public function uploadChunkIterator($file, $filePath, $uploadRequestInfo, $numberOfChunks, &$chunkNumber)
    {
        while ($chunk = fread($file, self::CHUNK_SIZE)) {
            $chunkNumber++;
            yield $this->uploadChunkAsync($filePath, $chunk, $uploadRequestInfo, $numberOfChunks, $chunkNumber);
        }
    }

    /**
     * Starts the upload process. Registers a file upload with Bynder and returns authorisation information to allow
     * uploading to the Amazon S3 bucket-endpoint.
     *
     * @param $filePath
     *
     * @return Promise\Promise Relevant S3 file information, necessary for the file upload.
     * @throws Exception
     */
    private function requestUploadInformationAsync($filePath)
    {
        return $this->requestHandler->sendRequestAsync('POST', 'api/upload/init',
            [
                'form_params' => ['filename' => $filePath]
            ]
        );
    }

    /**
     * The upload chunk logic function. Gets the closest Amazon endpoint, uploads the chunk to Amazon and registers it
     *  in Bynder.
     *
     * @param $filePath
     * @param $chunk
     * @param $uploadRequestInfo
     * @param $numberOfChunks
     * @param $chunkNumber
     * @return Promise\PromiseInterface|Promise\FulfilledPromise Value returned not used for next steps, just need to make sure it works.
     */
    private function uploadChunkAsync($filePath, $chunk, $uploadRequestInfo, $numberOfChunks, $chunkNumber)
    {
        return $this->amazonApi->uploadPartToAmazon($filePath, $this->awsBucket, $uploadRequestInfo,
            $chunkNumber, $chunk, $numberOfChunks)
            ->then(
                function () use ($uploadRequestInfo, $chunkNumber) {
                    return $this->registerChunkAsync($uploadRequestInfo, $chunkNumber);
                }
            );
    }

    /**
     * Gets the closest Amazon S3 bucket location to upload to.
     *
     * @return Promise\FulfilledPromise Amazon S3 location url.
     * @throws Exception
     */
    private function getClosestUploadEndpoint()
    {
        if (isset($this->awsBucket)) {
            return new Promise\FulfilledPromise($this->awsBucket);
        } else {
            return $this->requestHandler->sendRequestAsync('GET', 'api/upload/endpoint')
                ->then(
                    function ($result) {
                        $this->awsBucket = $result;
                        return $result;
                    });
        }
    }

    /**
     * Registers a temporary chunk in Bynder.
     *
     * @param $uploadRequestInfo
     * @param $chunkNumber
     *
     * @return Promise\Promise
     * @throws Exception
     */
    private function registerChunkAsync($uploadRequestInfo, $chunkNumber)
    {
        $s3Filename = sprintf("%s/p%d", $uploadRequestInfo['s3_filename'], $chunkNumber);

        $data = [
            'id' => $uploadRequestInfo['s3file']['uploadid'],
            'targetid' => $uploadRequestInfo['s3file']['targetid'],
            'filename' => $s3Filename,
            'chunkNumber' => $chunkNumber,
        ];
        return $this->requestHandler->sendRequestAsync(
            'POST',
            sprintf('api/v4/upload/%s/', $uploadRequestInfo['s3file']['uploadid']),
            ['form_params' => $data]
        );
    }

    /**
     * Finalizes the file upload when all chunks finished uploading and registers it in Bynder.
     *
     * @param $uploadRequestInfo
     * @param $chunkNumber
     *
     * @return Promise\Promise
     * @throws Exception
     */
    private function finalizeUploadAsync($uploadRequestInfo, $chunkNumber)
    {
        $s3Filename = sprintf("%s/p%d", $uploadRequestInfo['s3_filename'], $chunkNumber);

        $data = [
            'id' => $uploadRequestInfo['s3file']['uploadid'],
            'targetid' => $uploadRequestInfo['s3file']['targetid'],
            's3_filename' => $s3Filename,
            'chunks' => $chunkNumber,
        ];
        return $this->requestHandler->sendRequestAsync(
            'POST',
            sprintf('api/v4/upload/%s/', $uploadRequestInfo['s3file']['uploadid']),
            ['form_params' => $data]
        );
    }

    /**
     * Polls Bynder to confirm all chunks were uploaded and registered successfully in Bynder.
     *
     * @param $finalizeResponse
     *
     * @return Promise\Promise Returns whether or not the file was uploaded successfully.
     * @throws Exception
     */
    private function hasFinishedSuccessfullyAsync($finalizeResponse)
    {
        // Again using an Iterator function to generate the threads.
        $promises = $this->pollStatusIterator($finalizeResponse);
        $eachPromises = new Promise\EachPromise($promises, [
            'concurrency' => 1,
            'fulfilled' => function ($pollStatus, $i, $promise) {
                if ($pollStatus != null) {
                    if (!empty($pollStatus['itemsDone'])) {
                        $promise->resolve($pollStatus['itemsDone']);
                    }
                    if (!empty($pollStatus['itemsFailed'])) {
                        $promise->resolve(false);
                    }
                }
                return false;
            }
        ]);
        return $eachPromises->promise();
    }

    /**
     * Generates polling promises, sleeping between each request and limiting it to 1 thread to make sure enough time
     * passes after file upload.
     *
     * @param $finalizeResponse
     *
     * @return \Generator
     * @throws Exception
     */
    public function pollStatusIterator($finalizeResponse)
    {
        $iterations = 0;
        while ($iterations < self::MAX_POLLING_ITERATIONS) {
            $delay = $iterations == 0 ? 0 : self::POLLING_IDLE_TIME;
            yield $this->pollStatusAsync(['items' => $finalizeResponse['importId']], $delay);
            $iterations++;
        }
    }

    /**
     * Checks if the file has finished uploading.
     *
     * @param array $query The import ID of the file to check.
     *
     * @param int $delay
     *
     * @return Promise\Promise An array of the number successful and failed uploads.
     * @throws Exception
     */
    private function pollStatusAsync($query, $delay = 0)
    {
        return $this->requestHandler->sendRequestAsync(
            'GET',
            'api/v4/upload/poll/',
            ['query' => $query, 'delay' => $delay]
        );
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
    private function saveMediaAsync($data)
    {
        $uri = "api/v4/media/save/";
        if (isset($data['mediaId'])) {
            $uri = sprintf("api/v4/media/" . $data['mediaId'] . "/save/");
            unset($data['mediaId']);
        }
        return $this->requestHandler->sendRequestAsync('POST', $uri, ['form_params' => $data]);
    }
}