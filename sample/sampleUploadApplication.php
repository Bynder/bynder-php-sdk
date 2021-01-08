<?php
require_once('vendor/autoload.php');

use Bynder\Api\BynderClient;
use Bynder\Api\Impl\OAuth2;

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
        'asset:read',
        'asset:write',
    ]) . "\n\n";

    $code = readline('Enter code: ');

    if ($code == null) {
        exit;
    }

    $token = $bynder->getAccessToken($code);
    var_dump($token);
}


/* If we have a token stored
    $token = new \League\OAuth2\Client\Token\AccessToken([
        'access_token' => '',
        'refresh_token' => '',
        'expires' => 123456789
    ]);
 */


try {
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

    try {
        $temp;
        $attempts = 0;
        retry:
        $mediaItemPromise = $assetBankManager->getMediaInfo($sampleMediaId, $query);
        $mediaItem = $mediaItemPromise->wait();
        if ($mediaItem == null || $mediaItem['id'] != $sampleMediaId) {
            throw new Exception('Media Not found.');
        }
        var_dump($mediaItem);
    } catch (Exception $e) {
        if ($attempts < 5) {
            $attempts++;
            echo 'Failed to get media - trying again...' . PHP_EOL;
            sleep(5);
            goto retry;
        }
        echo 'Failed to get media after retrying 5 times. ' . $e->getMessage() . PHP_EOL;
    }

    $data = [
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
