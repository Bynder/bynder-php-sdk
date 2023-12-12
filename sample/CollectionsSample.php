<?php
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/sample_config.php');
use Bynder\Api\BynderClient;
use Bynder\Api\Impl\OAuth2;

try {
    // instantiate BynderClient to make API requests for portal, client id, client secret with redirect uri
    $bynder = new BynderClient(new Oauth2\Configuration(
        $bynderDomain,
        $redirectUri,
        $clientId,
        $clientSecret,
        $token,
        ['timeout' => 5] // Guzzle HTTP request options
    ));

    // if no access token, need to use OAuth flow to authorize and get access code, then use code to get token
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
                'collection:read',
                'collection:write'
            ]) . "\n\n";

        $code = readline('Enter code: ');

        if ($code == null) {
            echo("Failed to get access token");
            exit;
        }

        $token = $bynder->getAccessToken($code);
    }

    $assetBankManager = $bynder->getAssetBankManager();

    // Get Collections List.
    // optional filter
    $collectionQueryFilter = [
        'count' => true,
        'limit' => 20
    ];
    $collectionListPromise = $assetBankManager->getCollections($collectionQueryFilter );
    $collectionsList = $collectionListPromise->wait();

    // print collection list, each collection result
    if (!empty($collectionsList) && !empty($collectionsList['collections'])) {
        foreach ($collectionsList['collections'] as $collection) {
            echo("Collection ID: " . $collection['id'] . "\n");
            var_dump($collection);
        }
    }

    // get collection assets for a collection
    $collectionsAssetPromise = $assetBankManager->getCollectionAssets($GET_COLLECTION_ASSETS_ID);
    $collectionAssets = $collectionsAssetPromise->wait();

    if (!empty($collectionAssets)) {
        echo("Collection Asset IDs for ID: " . $GET_COLLECTION_ASSETS_ID);
        var_dump($collectionAssets);
    }

} catch (Exception $e) {
    var_dump($e);
}
?>
