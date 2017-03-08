<?php

require_once('vendor/autoload.php');

use Bynder\Api\BynderApiFactory;
use Bynder\Api\Impl\BynderApi;

$settings = array(
    'consumerKey' => BYNDER_CONSUMER_KEY,
    'consumerSecret' => BYNDER_CONSUMER_SECRET,
    'token' => BYNDER_CLIENT_KEY,
    'tokenSecret' => BYNDER_CLIENT_SECRET,
    'baseUrl' => BYNDER_URL
);

$haveTokens = true;

try {

    $bynderApi = BynderApiFactory::create($settings);

    // Deprecated username/password login
    // $tokens = $bynderApi->userLogin('username', 'password')->wait();

    // If we want to test the Login functionality make sure we don't pass the access token and secret in the settings.
    if (!$haveTokens && !isset($_GET['oauth_token'])) {
        // Get the request token
        $token = $bynderApi->getRequestToken()->wait();
        $tokenArray = explode('&', $token);
        // Storing this for later use because we're about to do a redirect.
        file_put_contents('tokens.txt', json_encode($tokenArray));
        $token = explode('=', $tokenArray[0])[1];
        $tokenSecret = explode('=', $tokenArray[1])[1];
        $query = array(
            'oauth_token' => $token,
            // Would be the url pointing to this script for example.
            'callback' => 'CALLBACK URL'
        );

        // Authorise the request token and redirect the user to the login page.
        $requestTokens = $bynderApi->authoriseRequestToken($query)->wait();
        preg_match("/redirectToken=(.*)\"/", $requestTokens, $output_array);
        header('Location: ' . BYNDER_URL . 'login/?redirectToken=' . $output_array[1]);
        exit();
    } // Here we're handling a redirect after a login.
    elseif (!$haveTokens) {
        // Get the request tokens we stored earlier.
        $tokens = json_decode(file_get_contents('tokens.txt'), true);
        $token = explode('=', $tokens[0])[1];
        $tokenSecret = explode('=', $tokens[1])[1];
        $settings = array(
            'consumerKey' => BYNDER_CONSUMER_KEY,
            'consumerSecret' => BYNDER_CONSUMER_SECRET,
            'token' => $token,
            'tokenSecret' => $tokenSecret,
            'baseUrl' => BYNDER_URL
        );
        $bynderApi = BynderApiFactory::create($settings);

        // Exchanging the authorised request token for an access token.
        $token = $bynderApi->getAccessToken()->wait();
    }

    $assetBankManager = $bynderApi->getAssetBankManager();

    // Get Brands. Returns a Promise.
    $brandsListPromise = $assetBankManager->getBrands();
    //Wait for the promise to be resolved.
    $brandsList = $brandsListPromise->wait();
    var_dump($brandsList);

    // Get Media Items list.
    // Optional filter.
    $query = array(
        'count' => true,
        'limit' => 2,
        'type' => 'image',
        'versions' => 1
    );

    $mediaListPromise = $assetBankManager->getMediaList($query);
    $mediaList = $mediaListPromise->wait();
    var_dump($mediaList);

    // Get specific Media Item info.
    $mediaId = array_pop($mediaList['media'])['id'];
    $mediaItemPromise = $assetBankManager->getMediaInfo($mediaId, $query);
    $mediaItem = $mediaItemPromise->wait();
    var_dump($mediaItem);

    // Get Metaproperties.
    $metapropertiesListPromise = $assetBankManager->getMetaproperties();
    $metapropertiesList = $metapropertiesListPromise->wait();
    var_dump($metapropertiesList);

    // Get Tags.
    $tagsListPromise = $assetBankManager->getTags();
    $tagsList = $tagsListPromise->wait();
    var_dump($tagsList);

    $data = array(
        'filePath' => 'test.jpg',
        'brandId' => $brandsList[0]['id'],
        'name' => 'Image name',
        'description' => 'Image description'
    );
    $filePromise = $assetBankManager->uploadFileAsync($data);
    $fileInfo = $filePromise->wait();
    var_dump($fileInfo);
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
