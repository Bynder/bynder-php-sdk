<?php
require_once('vendor/autoload.php');

use Bynder\Api\BynderClient;
use Bynder\Api\Impl\OAuth2;
use Bynder\Api\Impl\PermanentTokens;

define('BYNDER_INTEGRATION_ID', '');

// When using OAuth2

$bynderDomain = 'portal.getbynder.com';
$redirectUri = '';
$clientId = '';
$clientSecret = '';
$token = null;

/* If we have a token stored
    $token = new \League\OAuth2\Client\Token\AccessToken([
        'access_token' => '',
        'refresh_token' => '',
        'expires' => 123456789
    ]);
 */

$bynder = new BynderClient(new Oauth2\Configuration(
    $bynderDomain,
    $redirectUri,
    $clientId,
    $clientSecret,
    $token,
    ['timeout' => 5] // Guzzle HTTP request options
));

if($token === null) {
    echo $bynder->getAuthorizationUrl([
        'offline',
        'current.user:read',
        'current.profile:read',
        'asset:read',
        'asset:write',
        'meta.assetbank:read',
        'asset.usage:read',
        'asset.usage:write',
    ]) . "\n\n";

    $code = readline('Enter code: ');

    if($code == null) {
        exit;
    }

    $token = $bynder->getAccessToken($code);
    var_dump($token);
}

// When using permanent tokens

$bynderDomain = 'portal.getbynder.com';
$token = '';

$configuration = new PermanentTokens\Configuration(
    $bynderDomain,
    $token,
    ['timeout' => 5] // Guzzle HTTP request options
);

$bynder = new BynderClient($configuration);

// Example calls

try {
    $currentUser = $bynder->getCurrentUser()->wait();
    var_dump($currentUser);

    if(isset($currentUser['profileId'])) {
        $roles = $bynder->getSecurityProfile($currentUser['profileId'])->wait();
    }

    $assetBankManager = $bynder->getAssetBankManager();

    // Get Brands. Returns a Promise.
    $brandsListPromise = $assetBankManager->getBrands();
    // Wait for the promise to be resolved.
    $brandsList = $brandsListPromise->wait();
    var_dump($brandsList);

    // Get Media Items list.
    // Optional filter.
    $query = [
        'count' => true,
        'limit' => 2,
        'type' => 'image',
        'versions' => 1
    ];

    $mediaListPromise = $assetBankManager->getMediaList($query);
    $mediaList = $mediaListPromise->wait();
    var_dump($mediaList);

    // Get specific Media Item info.
    $mediaId = array_pop($mediaList['media'])['id'];
    $mediaItemPromise = $assetBankManager->getMediaInfo($mediaId, $query);
    $mediaItem = $mediaItemPromise->wait();
    var_dump($mediaItem);

    // Get Metaproperties.
    $metapropertiesListPromise = $assetBankManager->getMetaproperties();
    $metapropertiesList = $metapropertiesListPromise->wait();
    var_dump($metapropertiesList);

    // Get Tags.
    $tagsListPromise = $assetBankManager->getTags();
    $tagsList = $tagsListPromise->wait();
    var_dump($tagsList);

    // Get SmartFilters.
    $smartFilterListPromise = $assetBankManager->getSmartfilters();
    $smartFilterList = $smartFilterListPromise->wait();
    var_dump($smartFilterList);

    // Upload a file and create an Asset.
    $data = [
        // Will need to create this file for successful test call
        'filePath' => 'image.png',
        'brandId' => $brandsList[0]['id'],
        'name' => 'Image name',
        'description' => 'Image description'
    ];
    $filePromise = $assetBankManager->uploadFileAsync($data);
    $fileInfo = $filePromise->wait();
    var_dump($fileInfo);

    if(BYNDER_INTEGRATION_ID == '') {
        return;
    }

    // Create Asset usage.
    $usageCreatePromise = $assetBankManager->createUsage(
        [
            'integration_id' => BYNDER_INTEGRATION_ID,
            'asset_id' => $mediaId,
            'timestamp' =>  date(DateTime::ISO8601),
            'uri' => '/posts/1',
            'additional' => 'Testing usage tracking'
        ]
    );
    $usageCreated = $usageCreatePromise->wait();
    var_dump($usageCreated);

    // Create another Asset usage.
    $usageCreatePromise = $assetBankManager->createUsage(
        [
            'integration_id' => BYNDER_INTEGRATION_ID,
            'asset_id' => $mediaId,
            'timestamp' => date(DateTime::ISO8601),
            'uri' => '/posts/2',
            'additional' => 'Testing usage tracking'
        ]
    );
    $usageCreated = $usageCreatePromise->wait();
    var_dump($usageCreated);

    // Retrieve Asset usage.
    $retrieveUsages = $assetBankManager->getUsage(
        [
            'asset_id' => $mediaId
        ]
    )->wait();
    var_dump($retrieveUsages);

    // Delete Asset usage and retrieve again.
    $deleteUSages = $assetBankManager->deleteUSage(
        [
            'integration_id' => BYNDER_INTEGRATION_ID,
            'asset_id' => $mediaId,
            'uri' => '/posts/2'
        ]
    )->wait();
    $retrieveUsages = $assetBankManager->getUsage(
        [
            'asset_id' => $mediaId
        ]
    )->wait();
    var_dump($retrieveUsages);
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
