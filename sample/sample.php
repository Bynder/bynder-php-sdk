<?php
require_once('vendor/autoload.php');

use Bynder\Api\BynderClient;
use Bynder\Api\Impl\OAuth2;

define('BYNDER_INTEGRATION_ID', '');

// When using OAuth2

$token = null;
$bynder = null;

$conf = parse_ini_file('./sample_config.ini', 1)['oauth2'];

$bynderDomain = $conf['BYNDER_DOMAIN'];
$redirectUri = $conf['REDIRECT_URI'];
$clientId = $conf['CLIENT_ID'];
$clientSecret = $conf['CLIENT_SECRET'];
if ($conf['TOKEN'] !== null && $conf['TOKEN'] !== '') {
    $token = $conf['TOKEN'];
}

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

if ($token === null || $token === '') {
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

    if ($code == null) {
        exit;
    }

    $token = $bynder->getAccessToken($code);
    var_dump($token);
}

// Example calls

try {
    $currentUser = $bynder->getCurrentUser()->wait();
    var_dump($currentUser);

    if (isset($currentUser['profileId'])) {
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
    $filePath = 'image.png';
    $data = [
        // Will need to create this file for successful test call
        'brandId' => $brandsList[0]['id'],
        'name' => 'Image name',
        'description' => 'Image description'
    ];
    $filePromise = $assetBankManager->uploadFileAsync($filePath, $data);
    $fileInfo = $filePromise->wait();
    var_dump($fileInfo);

    if (BYNDER_INTEGRATION_ID == '') {
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
