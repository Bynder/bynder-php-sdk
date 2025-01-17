<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/sample_config.php');
use Bynder\Api\BynderClient;
use Bynder\Api\Impl\OAuth2;


try {
    // instantiate BynderClient, redirectUri and token are null (client credentials)
    $bynder = new BynderClient(new Oauth2\Configuration(
        $bynderDomain,
        null,
        $clientId,
        $clientSecret,
        null,
        ['timeout' => 5] // Guzzle HTTP request options
    ));

    // use client credentials grant type to get access token
    if ($token === null) {
        $token = $bynder->getAccessTokenClientCredentials();
    }

    $assetBankManager = $bynder->getAssetBankManager();

    // Get Brands. Returns a Promise.
    $brandsListPromise = $assetBankManager->getBrands();
    // Wait for the promise to be resolved.
    $brandsList = $brandsListPromise->wait();

    if (!empty($brandsList)) {
        foreach ($brandsList as $brand) {
            echo("Brand ID: " . $brand['id'] . "\n");
            var_dump($brand);
        }
    }

    // get derivatives
    $derivativesPromise = $assetBankManager->getDerivatives();
    $derivativesList = $derivativesPromise->wait();

    if (!empty($derivativesList)) {
        echo("Derivatives: " . "\n");
        var_dump($derivativesList);
    }

    // Get Media Items list.
    // Optional filter.
    $query = [
        'count' => true,
        'limit' => 10
    ];

    $mediaListPromise = $assetBankManager->getMediaList($query);
    $mediaList = $mediaListPromise->wait();

    // outputs list of media items, print media item
    if (!empty($mediaList) && !empty($mediaList['media'])) {
        foreach ($mediaList['media'] as $media) {
            echo("Media ID: " . $media['id'] . "\n");
            var_dump($media);
        }
    }
} catch (Exception $e) {
    var_dump($e);
}

?>

