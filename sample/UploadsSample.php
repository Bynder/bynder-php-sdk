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

    // Upload a file and create an Asset.
    // Get Brands. Returns a Promise.
    $brandsListPromise = $assetBankManager->getBrands();

    // Wait for the promise to be resolved.
    $brandsList = $brandsListPromise->wait();

    // get brand to upload to
    if (!empty($brandsList)) {
        echo("Uploading asset to brand id: " . $brandsList[0]['id']);
        $data = [
            // Will need to create this file for successful test call
            'filePath' => __DIR__  . '/image.png',
            'brandId' => $brandsList[0]['id'],
            'name' => 'Image name',
            'description' => 'Image description'
        ];
        $filePromise = $assetBankManager->uploadFileAsync($data);
        $fileInfo = $filePromise->wait();
        var_dump($fileInfo);
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}
?>
