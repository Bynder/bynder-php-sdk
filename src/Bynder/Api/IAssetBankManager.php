<?php

/**
 * Copyright (c) Bynder. All rights reserved.
 * Licensed under the MIT License. For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// src/Bynder/Api/IAssetBankManager.php
namespace Bynder\Api;

/**
 * Interface representing operations available on the Bynder Asset Bank via API.
 */
interface IAssetBankManager
{
    /**
     * Gets a list of all Brands available.
     *
     * @return Promise with a list of all Brands.
     *
     * @throws GuzzleHttp\Exception\RequestException When request fails.
     */
    public function getBrands();

    /**
     * Gets a list of media using query information. The media information is
     * not complete, for example media items for media returned are not present.
     * For that client needs to call getMediaInfo.
     *
     * @param array $query Associative array of parameters to filter the results.
     * @return Promise with list of media items.
     *
     * @throws GuzzleHttp\Exception\RequestException When request fails.
     */
    public function getMediaList($query);

    /**
     * Gets all the information for a specific media identifier. This is needed
     * to get the media items of a media.
     *
     * @param string $mediaId The Bynder media identifier.
     * @param boolean $versions Include info about the different versions available.
     * @return Promise with media information for specified media-item.
     *
     * @throws GuzzleHttp\Exception\RequestException When request fails.
     */
    public function getMediaInfo($mediaId, $versions);

    /**
     * Gets a dictionary of the metaproperties. The key of the dictionary
     * returned is the name of the metaproperty.
     *
     * @param array $query Associative array of parameters to filter the results.
     * @return Promise Dictionary of all the metaproperties.
     *
     * @throws GuzzleHttp\Exception\RequestException When request fails.
     */
    public function getMetaproperties($query);

    /**
     * Gets a list of all tags available.
     *
     * @param array $query Associative array of parameters to filter the results.
     * @return Promise List of all tags.
     *
     * @throws GuzzleHttp\Exception\RequestException When request fails.
     */
    public function getTags($query);

    /**
     * Gets a list of all categories available.
     *
     * @return Promise List of all categories.
     *
     * @throws GuzzleHttp\Exception\RequestException When request fails.
     */
    public function getCategories();

    /**
     * Uploads a file.
     *
     * @param $data File data and information for upload.
     *      array(
     *         'filePath' => 'image.jpg',
     *         'brandId' => 'brandId',
     *         'name' => 'Image Name',
     *         'description' => 'Image description'
     *      );
     * @return Promise Uploaded file information.
     */
    public function uploadFileAsync($data);

    /**
     * Deletes a given media.
     *
     * @param string $mediaId The Bynder media identifier.
     * @return Promise Deletes media item and returns 204.
     *
     * @throws GuzzleHttp\Exception\RequestException When request fails.
     */
    public function deleteMedia($mediaId);

    /**
     * Returns existing custom derivatives for current account.
     * @link http://docs.bynder.apiary.io/#reference/account/derivative-operations/retrieve-derivatives
     *
     * @return Promise List of all categories.
     *
     * @throws GuzzleHttp\Exception\RequestException When request fails.
     */
    public function  getDerivatives();

}
