# Bynder PHP SDK
![Build](https://github.com/Bynder/bynder-php-sdk/workflows/Build/badge.svg)
[![Coverage Status](https://coveralls.io/repos/github/Bynder/bynder-php-sdk/badge.svg?branch=master)](https://coveralls.io/github/Bynder/bynder-php-sdk?branch=master)
[![Packagist Version](https://img.shields.io/packagist/v/bynder/bynder-php-sdk)](https://packagist.org/packages/bynder/bynder-php-sdk)
![Packagist Downloads](https://img.shields.io/packagist/dt/bynder/bynder-php-sdk)

The main goal of this SDK is to speed up the integration of Bynder customers who use PHP. Making it easier to connect to the Bynder API (https://bynder.docs.apiary.io) and executing requests on it.

## Requirements and dependencies

The PHP SDK requires the following in order to fully work:

- [`PHP >= 5.6`](https://secure.php.net/manual/en/book.curl.php), older versions of PHP not recommended
- [`curl`](https://secure.php.net/manual/en/book.curl.php), although you can use your own non-cURL client if you prefer

Composer should handle all the dependencies automatically.

## Composer package

The Bynder PHP SDK is published as a composer package in [packagist](https://packagist.org) and can be found here:

```
https://packagist.org/packages/bynder/bynder-php-sdk
```

## Installation

This SDK depends on a few libraries in order to work, installing it with Composer should take care of everything automatically.

To install the SDK with [Composer](http://getcomposer.org/). Run the following command at the root of the project:

```bash
composer require bynder/bynder-php-sdk
```

To use the SDK, we use Composer's [autoload](https://getcomposer.org/doc/00-intro.md#autoloading) in order to include all the files automatically:

```php
require_once('vendor/autoload.php');
```

## How to use it

This is a simple example on how to retrieve data from the Bynder asset bank. For a more detailed example of implementation refer to the [sample code](https://github.com/Bynder/bynder-php-sdk/blob/master/sample/sample.php).

Before executing any request to the Bynder API we need to instantiate the **BynderApi** class, the following example shows how to use the **BynderApiFactory** to construct a **BynderApi** instance:
```php
    $bynder = new BynderClient(new Configuration(
        $bynderDomain,
        $redirectUri,
        $clientId,
        $clientSecret
    ));
```

The SDK allows the usage of the [Guzzle request options](http://docs.guzzlephp.org/en/latest/request-options.html).
This can be done by passing the last argument when initiating the
Configuration object:

```php
    $requestOptions = ['proxy' => 'http://MY-PROXY.URL:PORT_NUM'];
    $bynderApi = BynderClient(new Configuration(
       ...,
       $requestOptions
    ));

```

After getting the **BynderClient** service configured successfully we need to get an instance of the **AssetBankManager** in order to do any of the API calls relative to the Bynder Asset Bank module:

```php
 $assetBankManager = $bynder->getAssetBankManager();
```
And with this, we can start our request to the API, listed in the **Methods Available** section following. Short example of getting all the **Media Items**:

```php
 $mediaList = $assetBankManager->getMediaList();
```
This call will return a list with all the Media Items available in the Bynder environment. Note that some of the calls accept a query array in order to filter the results via the API call params (see [Bynder API Docs](http://docs.bynder.apiary.io/)) for more details.
For instance, if we only wanted to retrieve **2 images** here is what the call would look like:
```php
    $mediaList = $assetBankManager->getMediaList(
        [
          'limit' => 2,
          'type' => 'image'
        ]
   );
```

All the calls are **Asynchronous**, which means they will return a **Promise** object, making it a bit more flexible in order to adjust to any kind of application.
Again, for a more thorough example there is a sample [application use case](sample/sample.php) in this repo.

### Client Credentials

OAuth can be used via authorization code or client credentials. To use client credentials, initialize a Bynder client 
with OAuth2 Configuration and make call to get a token via:

`$bynder->getAccessTokenClientCredentials();`

Sample file found in `sample/OAuthClientCredentialsSample.php`.

`php OAuthClientCredentialsSample.php`

## Methods Available
These are the methods currently available on the **Bynder PHP SDK**, refer to the [Bynder API Docs](http://docs.bynder.apiary.io/)) for more specific details on the calls.

#### BynderClient:
Handles the process of generating and setting the access token required for the
requests to the API. Also has calls related to users.
```php
    getAssetBankManager();
    getAuthorizationUrl();
    getAccessToken();
    getUsers();
    getUser($userId, $query);
    getCurrentUser();
    getSecurityProfile($profileId);
```


#### AssetBankManager:
All the Asset Bank related calls, provides information and access to
Media management.
```php
    getBrands();
    getMediaList($query);
    getMediaInfo($mediaId, $versions);
    getMetaproperties();
    getMetaproperty($propertyId);
    getMetapropertyDependencies($propertyId);
    getMetapropertyOptions($query);
    getMetapropetryGlobalOptionDependencies();
    getMetapropertyOptionDependencies($propertyId);
    getMetapropertySpecificOptionDependencies($propertyId, $optionId, $query);
    getTags();
    getCategories();
    getSmartfilters();
    uploadFileAsync($data);
    deleteMedia($mediaId);
    modifyMedia($mediaId, array $data);
    getDerivatives();
    getMediaDownloadLocation($mediaId, $type = 'original');
    getMediaDownloadLocationByVersion($mediaId, $version);
    getMediaDownloadLocationForAssetItem($mediaId, $itemId, $hash = false);
    createUsage($query);
    getUsage($query);
    deleteUsage($query);
    getCollections($query);
    getCollectionAssets($collectionId);
```

## Tests

### Using Docker

Build the Docker image and tag it:
```bash
docker build . -t bynder-php-sdk-tests
```

Run the tests:
```bash
docker run bynder-php-sdk-tests
```

### Running it locally

Install dependencies as mentioned above (which will resolve [PHPUnit](http://packagist.org/packages/phpunit/phpunit)), then you can run the test suite:

```bash
./vendor/bin/phpunit tests
```

Or to run an individual test file:

```bash
./vendor/bin/phpunit tests/UtilTest.php
```

### Sample Files Functionality Testing

Scripts within `sample` contain code to execute corresponding functionalities. The purpose is to demonstrate how methods
are called and provide a convenient method to execute functions.

Within `sample` create a file called `sample_config.php`. This file will be referenced from sample files.

Make sure all values are populated correctly before running sample files.


Example `sample_config.php` file content:
```php
<?php
    $bynderDomain = "portal.bynder.com";
    $redirectUri = "https://google.com";
    $clientId = <your OAuth2 client id>;
    $clientSecret = <your OAuth2 client secret>;
    $token = null;

    // provide corresponding values to be used within API calls
    // media id for info
    $MEDIA_ID_FOR_INFO = "C078E8EE-C13A-4DA5-86EC8D6F335364EB";
    // media id for download url
    $MEDIA_ID_FOR_DOWNLOAD_URL = "C078E8EE-C13A-4DA5-86EC8D6F335364EB";
    // media id for download url
    $MEDIA_ITEM_ID_FOR_SPECIFIC_DOWNLOAD_URL = "C83B261D-715F-4188-809FE1214175A753";
    // media id for renaming
    $MEDIA_ID_FOR_RENAME = "C078E8EE-C13A-4DA5-86EC8D6F335364EB";
    // media id for removal
    $MEDIA_ID_FOR_REMOVAL = "C078E8EE-C13A-4DA5-86EC8D6F335364EB";
    // collection id to get assets for
    $GET_COLLECTION_ASSETS_ID = "615F03BB-D986-4786-B2C085D2F0718230";

    // metaproperty id to get info for
    $METAPROPERTY_ID_FOR_INFO = "0D563E99-218C-4613-86232D416EB7EA8A";
    // metaproperty option id to get info for
    $METAPROPERTY_OPTION_ID_FOR_INFO = "3C65AFA5-AC94-4223-A54757F373D209D6";
    // metaproperty id to get dependency info for
    $METAPROPERTY_ID_FOR_DEPENDENCY_INFO = "0D563E99-218C-4613-86232D416EB7EA8A";
    // metaproperty id for specific option dependency
    $METAPROPERTY_ID_FOR_SPECIFIC_OPTION_DEPEND = "0D563E99-218C-4613-86232D416EB7EA8A";
    // metaproperty option id for specific option
    $METAPROPERTY_OPTION_ID_FOR_SPECIFIC_OPTION_DEPEND = "DF1CF731-EFDF-484D-84BFD5CF8835B9D7";

    // media id used for creating asset usage
    $MEDIA_ID_FOR_ASSET_USAGE="C078E8EE-C13A-4DA5-86EC8D6F335364EB";
    // integration id used for asset usage
    $INTEGRATION_ID_FOR_ASSET_USAGE="0191a303-9d99-433e-ada4-d244f37e1d7d";
?>
```
Within each sample file, OAuth credentials are read in from `sample_config.php`.
Scripts will output authorization url to navigate to retrieve access code (will not open browser automatically, user must click link).
Access code is then provided to terminal prompt to retrieve an access token for API calls afterward.

### Command Line Instructions

Make sure both `composer` and `php` are installed locally. From root directory run `composer install` to install packages
form `composer.json`. Navigate to `sample` directory.

#### Brands Sample
```bash
php BrandsSample.php
```

Methods Used:
* getBrands()

#### Collections Sample
```bash
php CollectionsSample.php
```

Methods Used:
* getCollections($query)
* getCollectionAssets($collectionId)

#### Media Sample
```bash
php MediaSample.php
```

Methods Used:
* getDerivatives()
* getMediaList($query)
* getMediaInfo($mediaId)
* getMediaDownloadLocation($mediaId)
* getMediaDownloadLocationByVersion($mediaId, $version)
* getMediaDownloadLocationForAssetItem($mediaId, $itemId)
* modifyMedia($mediaId, $data)
* getMediaInfo($mediaId)
* deleteMedia($mediaId)

#### Metaproperties Sample
```bash
php MetapropertiesSample.php
```

Methods Used:
* getMetaproperties()
* getMetaproperty($metapropertyId)
* getMetapropertyDependencies($metapropertyId)
* getMetapropertyOptions($query)
* getMetapropetryGlobalOptionDependencies()
* getMetapropertyOptionDependencies($metapropertyId)
* getMetapropertySpecificOptionDependencies($metapropertyId, $metapropertyOptionId, $array)

#### Smart Filters Sample
```bash
php SmartFiltersSample.php
```

Methods Used:
* getSmartfilters()


#### Tags Sample
```bash
php TagsSample.php
```

Methods Used:
* getTags()

#### Uploads Sample
```bash
php UploadsSample.php
```

Methods Used:
* uploadFileAsync($data)
* getBrands()

#### Usage Sample
```bash
php UsageSample.php
```

Methods Used:
* createUsage($data)
* getUsage($data)
* deleteUsage($data)


### Docker Instructions

Sample files can be executed within Docker container. Makefile contains corresponding commands to run/build Docker container.

`Dockerfile.dev` file is used for container.

Makefile commands are executed from root directory. Run with sudo if permission is needed.

If needed, pull latest `composer` Docker image `docker pull composer:latest`

#### Makefile commands:

Build and start up Docker container for PHP SDK using Docker Compose
```bash
make run-php-sdk-docker
```

Stop running Docker container for PHP SDK:
```bash
make stop-php-sdk-docker
```

Run sample file within Docker container (BrandsSample.php is replaced with target sample file):
```bash
make execute-php-sdk-sample sample-file-name=BrandsSample.php
```
