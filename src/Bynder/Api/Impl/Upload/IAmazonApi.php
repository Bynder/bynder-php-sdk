<?php

/**
 *
 * Copyright (c) Bynder. All rights reserved.
 *
 * Licensed under the MIT License. For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// src/Bynder/Api/Impl/Upload/IAmazonApi.php
namespace Bynder\Api\Impl\Upload;

interface IAmazonApi
{
    /**
     * Uploads a chunk of a file to an S3 bucket using multipart upload.
     *
     * @param $filePath
     * @param $uploadEndpoint
     * @param $uploadRequestInfo
     * @param $chunkNumber
     * @param $chunk
     * @param $numberOfChunks
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function uploadPartToAmazon(
        $filePath,
        $uploadEndpoint,
        $uploadRequestInfo,
        $chunkNumber,
        $chunk,
        $numberOfChunks
    );

}