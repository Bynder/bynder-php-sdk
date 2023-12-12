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

    // get info for specific metaproperty
    $metapropertyInfoPromise = $assetBankManager->getMetaproperty($METAPROPERTY_ID_FOR_INFO);
    $metapropertyInfo = $metapropertyInfoPromise->wait();

    if (!empty($metapropertyInfo)) {
        echo("Metaproperty Info for ID: " . $METAPROPERTY_ID_FOR_INFO);
        var_dump($metapropertyInfo);
    }

    // get metaproperty dependencies
    $metapropertyDependenciesPromises = $assetBankManager->getMetapropertyDependencies($METAPROPERTY_ID_FOR_DEPENDENCY_INFO);
    $metapropertyDependencies = $metapropertyDependenciesPromises->wait();

    if (!empty($metapropertyDependencies)) {
        echo("Metaproperty Dependencies for ID: " . $METAPROPERTY_ID_FOR_DEPENDENCY_INFO);
        var_dump($metapropertyDependencies);
    }

    // get metaproperty option ids
    $query = ["ids" => $METAPROPERTY_OPTION_ID_FOR_INFO];
    $metapropertyOptionsPromise = $assetBankManager->getMetapropertyOptions($query);
    $metapropertyOptions = $metapropertyOptionsPromise->wait();

    if (!empty($metapropertyOptions)) {
        echo("Metaproperty Options for ID: " . $METAPROPERTY_OPTION_ID_FOR_INFO);
        var_dump($metapropertyOptions);
    }

    // get metaproperty global option dependencies
    $metapropertyGlobalOptionDependenciesPromise = $assetBankManager->getMetapropetryGlobalOptionDependencies();
    $metapropertyGlobalOptionDependencies = $metapropertyGlobalOptionDependenciesPromise->wait();

    if (!empty($metapropertyGlobalOptionDependencies)) {
        echo("Metaproperty Global Option Dependencies" . "\n");
        var_dump($metapropertyGlobalOptionDependencies);
    }

    // get metaproperty option dependencies
    $metapropertyOptionDependenciesPromise = $assetBankManager->getMetapropertyOptionDependencies($METAPROPERTY_ID_FOR_DEPENDENCY_INFO);
    $metapropertyOptionsDependencies = $metapropertyOptionDependenciesPromise->wait();

    if (!empty($metapropertyOptionsDependencies)) {
        echo("Metaproperty Options Dependencies for ID: " . $METAPROPERTY_ID_FOR_DEPENDENCY_INFO . "\n");
        var_dump($metapropertyOptionsDependencies);
    }

    // get metaproperty specific option dependencies
    $metapropertySpecificOptionDependenciesPromise = $assetBankManager->getMetapropertySpecificOptionDependencies($METAPROPERTY_ID_FOR_SPECIFIC_OPTION_DEPEND, $METAPROPERTY_OPTION_ID_FOR_SPECIFIC_OPTION_DEPEND, ['includeGroupedResults' => false]);
    $metapropertySpecificOptionDependencies = $metapropertySpecificOptionDependenciesPromise->wait();

    if (!empty($metapropertySpecificOptionDependencies)) {
        echo("Metaproperty Specific Option Dependencies" . "\n");
        var_dump($metapropertySpecificOptionDependencies);
    }
} catch (Exception $e) {
    var_dump($e);
}
?>
