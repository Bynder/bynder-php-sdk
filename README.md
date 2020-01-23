# Bynder PHP SDK
[![Build Status](https://travis-ci.org/Bynder/bynder-php-sdk.svg?branch=master)](https://travis-ci.org/Bynder/bynder-php-sdk)
[![Coverage Status](https://coveralls.io/repos/github/Bynder/bynder-php-sdk/badge.svg)](https://coveralls.io/github/Bynder/bynder-php-sdk)

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

Install dependencies as mentioned above (which will resolve [PHPUnit](http://packagist.org/packages/phpunit/phpunit)), then you can run the test suite:

```bash
./vendor/bin/phpunit tests
```

Or to run an individual test file:

```bash
./vendor/bin/phpunit tests/UtilTest.php
```
