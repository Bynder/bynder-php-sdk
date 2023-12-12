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
            ]) . "\n\n";

        $code = readline('Enter code: ');

        if ($code == null) {
            echo("Failed to get access token");
            exit;
        }

        $token = $bynder->getAccessToken($code);
    }

    $assetBankManager = $bynder->getAssetBankManager();

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

    // get info for single media asset
    $mediaInfoPromise = $assetBankManager->getMediaInfo($MEDIA_ID_FOR_INFO);
    $mediaInfo = $mediaInfoPromise->wait();

    if (!empty($mediaInfo)) {
        echo("Media Info for ID: " . $mediaInfo['id']);
        var_dump($mediaInfo);
    }

    // get media download url
    $mediaDownloadUrlPromise = $assetBankManager->getMediaDownloadLocation($MEDIA_ID_FOR_INFO);
    $mediaDownloadUrl = $mediaDownloadUrlPromise->wait();

    if (!empty($mediaDownloadUrl)) {
        echo("Media Download URL for ID: " . $MEDIA_ID_FOR_INFO);
        var_dump($mediaDownloadUrl);
    }

    // modify name of asset
    $renameData = ['name' => 'PHP SDK Test'];
    $modifyMediaPromise = $assetBankManager->modifyMedia($MEDIA_ID_FOR_RENAME, $renameData);
    $modifyMediaResult = $modifyMediaPromise->wait();

    if (!empty($modifyMediaResult)) {
        echo("Modify Media for ID: " . $MEDIA_ID_FOR_RENAME);
        var_dump($modifyMediaResult);

        // get info for modified media asset
        $mediaInfoPromise = $assetBankManager->getMediaInfo($MEDIA_ID_FOR_RENAME);
        $mediaInfo = $mediaInfoPromise->wait();

        if (!empty($mediaInfo)) {
            echo("Media Info for ID: " . $mediaInfo['id']);
            var_dump($mediaInfo);
        }
    }

    // delete asset
    echo("Deleting Media ID: " . $MEDIA_ID_FOR_REMOVAL);
    $deleteMediaPromise = $assetBankManager->deleteMedia($MEDIA_ID_FOR_REMOVAL);
    $deleteMediaResult = $deleteMediaPromise->wait();
} catch (Exception $e) {
    var_dump($e);
}
?>
