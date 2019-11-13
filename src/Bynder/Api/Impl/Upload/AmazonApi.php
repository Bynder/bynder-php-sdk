<?php
/**
 *
 * Copyright (c) Bynder. All rights reserved.
 *
 * Licensed under the MIT License. For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bynder\Api\Impl\Upload;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;

class AmazonApi
{
    /**
     * Uploads a chunk of a file to an S3 bucket using multipart upload.
     *
     * @param  $filePath
     * @param  $uploadEndpoint
     * @param  $uploadRequestInfo
     * @param  $chunkNumber
     * @param  $chunk
     * @param  $numberOfChunks
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function uploadPartToAmazon(
        $filePath,
        $uploadEndpoint,
        $uploadRequestInfo,
        $chunkNumber,
        $chunk,
        $numberOfChunks
    ) {
        $finalKey = sprintf("%s/p%d", $uploadRequestInfo['multipart_params']['key'], $chunkNumber);
        $formData = [
            self::getFormDataParams('x-amz-credential', $uploadRequestInfo['multipart_params']['x-amz-credential']),
            self::getFormDataParams('X-Amz-Signature', $uploadRequestInfo['multipart_params']['X-Amz-Signature']),
            self::getFormDataParams('x-amz-algorithm', $uploadRequestInfo['multipart_params']['x-amz-algorithm']),
            self::getFormDataParams('x-amz-date', $uploadRequestInfo['multipart_params']['x-amz-date']),
            self::getFormDataParams('Policy', $uploadRequestInfo['multipart_params']['Policy']),
            self::getFormDataParams('key', $finalKey),
            self::getFormDataParams('acl', $uploadRequestInfo['multipart_params']['acl']),
            self::getFormDataParams('success_action_status',
                $uploadRequestInfo['multipart_params']['success_action_status']),
            self::getFormDataParams('Content-Type', $uploadRequestInfo['multipart_params']['Content-Type']),
            self::getFormDataParams('name', $filePath),
            self::getFormDataParams('chunk', $chunkNumber),
            self::getFormDataParams('chunks', $numberOfChunks),
            self::getFormDataParams('Filename', $finalKey),
            self::getFormDataParams('file', $chunk)
        ];

        $stack = HandlerStack::create(new CurlHandler());
        $oauthRequestClient = new Client([
            'base_uri' => $uploadEndpoint,
            'handler' => $stack,
        ]);

        return $oauthRequestClient->postAsync($uploadEndpoint, ['multipart' => $formData])
            ->then(
                function (ResponseInterface $response) {
                    return json_decode($response->getBody(), true);
                }
            );
    }

    private static function getFormDataParams($name, $contents)
    {
        return ['name' => $name, 'contents' => $contents];
    }
}