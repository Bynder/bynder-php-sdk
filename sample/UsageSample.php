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

    // Create Asset usage.
    $usageCreatePromise = $assetBankManager->createUsage(
        [
            'integration_id' => $INTEGRATION_ID_FOR_ASSET_USAGE,
            'asset_id' => $MEDIA_ID_FOR_ASSET_USAGE,
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
            'integration_id' => $INTEGRATION_ID_FOR_ASSET_USAGE,
            'asset_id' => $MEDIA_ID_FOR_ASSET_USAGE,
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
            'asset_id' => $MEDIA_ID_FOR_ASSET_USAGE
        ]
    )->wait();
    var_dump($retrieveUsages);

    // Delete Asset usage and retrieve again.
    $deleteUSages = $assetBankManager->deleteUSage(
        [
            'integration_id' => $INTEGRATION_ID_FOR_ASSET_USAGE,
            'asset_id' => $MEDIA_ID_FOR_ASSET_USAGE,
            'uri' => '/posts/2'
        ]
    )->wait();
    $retrieveUsages = $assetBankManager->getUsage(
        [
            'asset_id' => $MEDIA_ID_FOR_ASSET_USAGE
        ]
    )->wait();
    var_dump($retrieveUsages);

} catch (Exception $e) {
    var_dump($e);
}
?>
