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
     * @throws \Exception
     */
    public function getBrands()
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/brands/');
    }

    /**
     * Gets a list of all media available, params sent in $query will filter the results.
     *
     * @param array $query
     *
     * @see IAssetBankManager::getMediaList() for more information.
     * @return \GuzzleHttp\Promise\Promise
     * @throws \Exception
     */
    public function getMediaList($query = null)
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/media/',
            ['query' => $query]
        );
    }

    /**
     * Retrieves specific media information for $mediaId.
     *
     * @param string $mediaId
     * @param array  $query
     *
     * @see IAssetBankManager::getMediaInfo() for more information.
     * @return \GuzzleHttp\Promise\Promise
     * @throws \Exception
     */
    public function getMediaInfo($mediaId, $query = null)
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/media/' . $mediaId . '/',
            ['query' => $query]
        );
    }

    /**
     * Retrieves a dictionary of all metaproperties available, keyed by the
     * metaproperty name.
     *
     * @param array $query
     *
     * @return \GuzzleHttp\Promise\Promise
     * @throws \Exception
     * @see IAssetBankManager::getMetaproperties() for more information.
     */
    public function getMetaproperties($query = null)
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/metaproperties/',
            ['query' => $query]
        );
    }

    /**
     * Gets a specific meta property
     *
     * @param string $propertyId Meta property id
     *
     * @return \GuzzleHttp\Promise\Promise with the meta property.
     * @throws \Exception
     */
    public function getMetaproperty($propertyId)
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/metaproperties/' . $propertyId . '/');
    }

    /**
     * Gets all dependencies for meta property
     *
     * @param string $propertyId Meta property id
     *
     * @return \GuzzleHttp\Promise\Promise with the meta property.
     * @throws \Exception
     */
    public function getMetapropertyDependencies($propertyId)
    {
        return $this->requestHandler->sendRequestAsync('GET',
            'api/v4/metaproperties/' . $propertyId . '/dependencies/');
    }

    /**
     * Gets a list of meta property options
     *
     * @param array $query Associative array of parameters to filter the results.
     *
     * @return \GuzzleHttp\Promise\Promise with all requested meta property options.
     * @throws \Exception
     */
    public function getMetapropertyOptions($query)
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/metaproperties/options/',
            ['query' => $query]
        );
    }

    /**
     * Gets a list of all meta property option dependencies (globally)
     *
     * @return \GuzzleHttp\Promise\Promise with all meta property options dependencies.
     * @throws \Exception
     */
    public function getMetapropetryGlobalOptionDependencies()
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/metaproperties/options/dependencies/');
    }

    /**
     * Gets a list of all meta property option dependencies for a specific property
     *
     * @param string $propertyId Meta property id
     *
     * @return \GuzzleHttp\Promise\Promise with all meta property options dependencies.
     * @throws \Exception
     */
    public function getMetapropertyOptionDependencies($propertyId)
    {
        return $this->requestHandler->sendRequestAsync('GET',
            'api/v4/metaproperties/' . $propertyId . '/options/dependencies/');
    }

    /**
     * Gets a list of all meta property option dependencies for a specific option
     *
     * @param string $propertyId Meta property id
     * @param string $optionId   Option id
     * @param array  $query      Associative array of parameters to filter the results.
     *
     * @return \GuzzleHttp\Promise\Promise with all meta property options dependencies.
     * @throws \Exception
     */
    public function getMetapropertySpecificOptionDependencies($propertyId, $optionId, $query)
    {
        return $this->requestHandler->sendRequestAsync('GET',
            'api/v4/metaproperties/' . $propertyId . '/options/' . $optionId . '/dependencies/',
            ['query' => $query]
        );
    }

    /**
     * Retrieves a list of all tags available.
     *
     * @param array $query
     *
     * @see IAssetBankManager::getTags() for more information.
     * @return \GuzzleHttp\Promise\Promise
     * @throws \Exception
     */
    public function getTags($query = null)
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/tags/',
            ['query' => $query]
        );
    }

    /**
     * Retrieves a list of all categories available.
     *
     * @see IAssetBankManager::getCategories() for more information.
     * @throws \Exception
     */
    public function getCategories()
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/categories');
    }

    /**
     * Uploads a file to the Asset Bank.
     *
     * @param array $data File data and information for upload.
     *
     * @see IAssetBankManager::uploadFileAsync() for more information.
     * @return \GuzzleHttp\Promise\Promise
     * @throws \Exception
     */
    public function uploadFileAsync($data)
    {
        return $this->fileUploader->uploadFile($data);
    }

    /**
     * Deletes a media item from the asset bank.
     *
     * @param string $mediaId
     *
     * @see IAssetBankManager::deleteMedia() for more information.
     *
     * @return \GuzzleHttp\Promise\Promise
     * @throws \Exception
     */
    public function deleteMedia($mediaId)
    {
        return $this->requestHandler->sendRequestAsync('DELETE', 'api/v4/media/' . $mediaId . '/');
    }

    /**
     * Modifies existing assets fields
     *
     * @link http://docs.bynder.apiary.io/#reference/assets/specific-asset-operations/modify-asset
     *
     * @param string $mediaId
     * @param array  $data File information to be set.
     *
     * @see  IAssetBankManager::modifyMedia() for more information.
     *
     * @return \GuzzleHttp\Promise\Promise
     * @throws \Exception
     */
    public function modifyMedia($mediaId, array $data)
    {
        return $this->requestHandler->sendRequestAsync('POST', 'api/v4/media/' . $mediaId . '/',
            ['form_params' => $data]
        );
    }

    /**
     * Returns existing custom derivatives for current account.
     *
     * @see IAssetBankManager::getDerivatives() for more information.
     * @throws \Exception
     */
    public function getDerivatives()
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/account/derivatives/');
    }

    /**
     * Gets the download location for a specific asset
     *
     * @param string $mediaId The Bynder media identifier (Asset id).
     * @param string $type    Type of files to download. Note that when multiple additional files are
     *                        available only the download url of the latest one will be returned.
     *                        E.g. additional, original. Default = original
     *
     * @return \GuzzleHttp\Promise\Promise
     * @throws \Exception
     */
    public function getMediaDownloadLocation($mediaId, $type = 'original')
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/media/' . $mediaId . '/download/',
            [
                'query' =>
                    ['type' => $type]
            ]
        );
    }

    /**
     * Gets the download location for a specific asset with a specific version
     *
     * @param string $mediaId The Bynder media identifier (Asset id).
     * @param int    $version Asset version to download.
     *
     * @return \GuzzleHttp\Promise\Promise with the download location for a specific asset.
     * @throws \Exception
     */
    public function getMediaDownloadLocationByVersion($mediaId, $version)
    {
        return $this->requestHandler->sendRequestAsync('GET',
            'api/v4/media/' . $mediaId . '/' . $version . '/download/');
    }

    /**
     * Gets the download location for a specific asset item
     *
     * @param string  $mediaId The Bynder media identifier (Asset id).
     * @param string  $itemId  The id of the specific asset item you’d like to download.
     * @param boolean $hash    Indicates whether or not to treat the itemId as a hashed item id.
     *
     * @return \GuzzleHttp\Promise\Promise with the download location for a specific asset.
     * @throws \Exception
     */
    public function getMediaDownloadLocationForAssetItem($mediaId, $itemId, $hash = false)
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/v4/media/' . $mediaId . '/download/' . $itemId . '/',
            [
                'query' =>
                    ['hash' => $hash]
            ]
        );
    }

    /**
     * Creates a usage record for a media asset.
     *
     * @param $query
     *
     * @return \GuzzleHttp\Promise\Promise Asset usage information.
     *
     * @throws \GuzzleHttp\Exception\RequestException When request fails.
     * @throws \Exception
     */
    public function createUsage($query)
    {
        return $this->requestHandler->sendRequestAsync('POST', 'api/media/usage',
            ['form_params' => $query]
        );
    }

    /**
     * Gets all the media assets usage records.
     *
     * @param $query
     *
     * @return \GuzzleHttp\Promise\Promise List of asset usage information.
     *
     * @throws \GuzzleHttp\Exception\RequestException When request fails.
     * @throws \Exception
     */
    public function getUsage($query)
    {
        return $this->requestHandler->sendRequestAsync('GET', 'api/media/usage',
            ['query' => $query]
        );
    }

    /**
     * Deletes a usage record of a media asset.
     *
     * @param $query
     *
     * @return \GuzzleHttp\Promise\Promise Response of asset usage delete.
     *
     * @throws \GuzzleHttp\Exception\RequestException When request fails.
     * @throws \Exception
     */
    public function deleteUSage($query)
    {
        return $this->requestHandler->sendRequestAsync('DELETE', 'api/media/usage',
            ['query' => $query]
        );
    }
}
