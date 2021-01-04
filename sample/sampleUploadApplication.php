<?php
require_once('vendor/autoload.php');

use Bynder\Api\BynderClient;
use Bynder\Api\Impl\OAuth2;
use Bynder\Api\Impl\PermanentTokens;


$bynderDomain = 'portal.getbynder.com';
$redirectUri = '';
$clientId = '';
$clientSecret = '';
$token = null;
$bynder = null;
$configuration = null;

$conf = parse_ini_file('./sample_config.ini', 1);
var_dump($conf['authMethod']);

if ($conf['authMethod'] == 'permanentTokens') {
    // When using Permanent Tokens

    $bynderDomain = $conf['permanentTokens']['bynderDomain'];
    $token = $conf['permanentTokens']['token'];
    $configuration = new PermanentTokens\Configuration(
        $bynderDomain,
        $token,
        ['timeout' => 5] // Guzzle HTTP request options
    );

    $bynder = new BynderClient($configuration);
} else {
    // When using OAuth2

    $bynderDomain = $conf['oauth2']['bynderDomain'];
    $redirectUri = $conf['oauth2']['redirectUri'];
    $clientId = $conf['oauth2']['clientId'];
    $clientSecret = $conf['oauth2']['clientSecret'];
    $token = $conf['oauth2']['token'];

    $bynder = new BynderClient(new Oauth2\Configuration(
        $bynderDomain,
        $redirectUri,
        $clientId,
        $clientSecret,
        $token,
        ['timeout' => 5] // Guzzle HTTP request options
    ));

    if ($token === null) {
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
}

/* If we have a token stored
    $token = new \League\OAuth2\Client\Token\AccessToken([
        'access_token' => '',
        'refresh_token' => '',
        'expires' => 123456789
    ]);
 */


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

    // Get Media Items list.
    // Optional filter.
    $query = [
        'count' => true,
        'limit' => 2,
        'type' => 'text',
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


    // Upload a file and create an Asset.
    $fileHandle = fopen('sample.txt', 'w');
    fwrite($fileHandle, 'sample');
    $filePath = 'sample.txt';
    $data = [
        // Will need to create this file for successful test call
        'brandId' => $brandsList[0]['id'],
        'name' => 'Sample name',
        'description' => 'Sample description'
    ];
    $filePromise = $assetBankManager->uploadFileAsync($filePath, $data);
    $fileInfo = $filePromise->wait();
    var_dump($fileInfo);

    // Upload to an existing Asset

    $sampleMediaId = $fileInfo['mediaid'];
    var_dump($sampleMediaId);
    $data = [
        // Will need to create this file for successful test call
        'mediaId' => $sampleMediaId,
        'name' => 'Sample name',
        'description' => 'Sample description'
    ];
    $filePromise = $assetBankManager->uploadFileAsync($filePath, $data);
    $fileInfo = $filePromise->wait();
    var_dump($fileInfo);
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
