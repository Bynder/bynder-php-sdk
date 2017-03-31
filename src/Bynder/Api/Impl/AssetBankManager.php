<?php

/**
 *
 * Copyright (c) Bynder. All rights reserved.
 *
 * Licensed under the MIT License. For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// src/Bynder/Api/Impl/AssetBankManager.php
namespace Bynder\Api\Impl;

use Bynder\Api\IAssetBankManager;
use Bynder\Api\Impl\Oauth\IOauthRequestHandler;
use Bynder\Api\Impl\Upload\FileUploader;

/**
 * Implementation of IAssetBankManager, providing operations available on the Bynder Asset Bank via API.
 */
class AssetBankManager implements IAssetBankManager
{

    /**
     * @var IOauthRequestHandler Request handler used to communicate with the API.
     */
    private $requestHandler;

    /**
     * @var FileUploader Used for file uploading operations.
     */
    private $fileUploader;

    /**
     * Initialises a new instance of the class.
     *
     * @param IOauthRequestHandler $requestHandler Request handler used to communicate with the API.
     */
    public function __construct(IOauthRequestHandler $requestHandler)
    {
        $this->requestHandler = $requestHandler;
        $this->fileUploader = FileUploader::create($requestHandler);
    }

    /**
     * Gets a list of all Brands available.
     *
     * @see IAssetBankManager::getBrands() for more information.
     */
    public function getBrands()
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/brands/');
    }

    /**
     * Gets a list of all media available, params sent in $query will filter the results.
     *
     * @param array $query
     * @see IAssetBankManager::getMediaList() for more information.
     */
    public function getMediaList($query = null)
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/media/',
            array(
                'query' => $query
            )
        );
    }

    /**
     * Retrieves specific media information for $mediaId.
     *
     * @param string $mediaId
     * @param array $query
     * @see IAssetBankManager::getMediaInfo() for more information.
     */
    public function getMediaInfo($mediaId, $query = null)
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/media/' . $mediaId . '/',
            array(
                'query' => $query
            )
        );
    }

    /**
     * Retrieves a dictionary of all metaproperties available, keyed by the
     * metaproperty name.
     *
     * @see IAssetBankManager::getMetaproperties() for more information.
     */
    public function getMetaproperties()
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/metaproperties/');
    }

    /**
     * Retrieves a list of all tags available.
     *
     * @see IAssetBankManager::getTags() for more information.
     */
    public function getTags()
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/tags/');
    }

    /**
     * Retrieves a list of all categories available.
     *
     * @see IAssetBankManager::getCategories() for more information.
     */
    public function getCategories()
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/categories');
    }

    /**
     * Uploads a file to the Asset Bank.
     *
     * @param array $data File data and information for upload.
     * @see IAssetBankManager::uploadFileAsync() for more information.
     */
    public function uploadFileAsync($data)
    {
        return $this->fileUploader->uploadFile($data);
    }

    /**
     * Deletes a media item from the asset bank.
     *
     * @param string $mediaId
     * @see IAssetBankManager::deleteMedia() for more information.
     */
    public function deleteMedia($mediaId)
    {
        return $this->requestHandler->sendRequestAsync('DELETE', 'api/v4/media/' . $mediaId . '/');
    }

}
