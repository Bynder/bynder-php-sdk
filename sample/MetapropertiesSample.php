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

    // Get Metaproperties.
    $metapropertiesListPromise = $assetBankManager->getMetaproperties();
    $metapropertiesList = $metapropertiesListPromise->wait();

    if (!empty($metapropertiesList)) {
        foreach ($metapropertiesList as $metaproperty) {
            echo("Metaproperty ID: " . $metaproperty['id']) . "\n";
            var_dump($metaproperty);
        }
    }
    var_dump($metapropertiesList);

} catch (Exception $e) {
    var_dump($e);
}
?>
