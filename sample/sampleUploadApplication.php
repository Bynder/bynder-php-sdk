<?php
require_once('vendor/autoload.php');

include('util/SetUpCredentials.php');

$creds = new SetUpCredentials();
$creds->setUp(array(
    'offline',
    'asset:read',
    'asset:write',
));
$bynder = $creds->getBynder();


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
