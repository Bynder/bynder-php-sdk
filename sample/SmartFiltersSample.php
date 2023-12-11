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

    // Get SmartFilters.
    $smartFilterListPromise = $assetBankManager->getSmartfilters();
    $smartFilterList = $smartFilterListPromise->wait();

    if (!empty($smartFilterList)) {
        foreach ($smartFilterList as $smartFilter) {
            echo("Smart Filter ID: " . $smartFilter['id'] . "\n");
            echo("Smart Filter Labels: " . print_r($smartFilter['labels'], 1)) . "\n";
            echo("Smart Filter Metaproperties: " . print_r($smartFilter['metaproperties'], 1)) . "\n";
        }
    }
} catch (Exception $e) {
    var_dump($e);
}
?>

