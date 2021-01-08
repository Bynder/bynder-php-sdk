<?php
require_once('vendor/autoload.php');

use Bynder\Api\BynderClient;
use Bynder\Api\Impl\OAuth2;

class SetUpCredentials
{
    private $token = null;
    private $bynder = null;

    function setUp($scopes)
    {
        $conf = parse_ini_file('./sample_config.ini', 1)['oauth2'];

        $bynderDomain = $conf['BYNDER_DOMAIN'];
        $redirectUri = $conf['REDIRECT_URI'];
        $clientId = $conf['CLIENT_ID'];
        $clientSecret = $conf['CLIENT_SECRET'];

        if ($this->conf['TOKEN'] !== null && $this->conf['TOKEN'] !== '') {
            $this->token = $this->conf['TOKEN'];
        }
        $this->bynder = new BynderClient(new Oauth2\Configuration(
            $bynderDomain,
            $redirectUri,
            $clientId,
            $clientSecret,
            $this->token,
            ['timeout' => 5] // Guzzle HTTP request options
        ));

        if ($this->token === null) {
            echo $this->bynder->getAuthorizationUrl($scopes) . "\n\n";

            $code = readline('Enter code: ');

            if ($code == null) {
                exit;
            }

            $this->token = $this->bynder->getAccessToken($code);
            var_dump($this->token);
        }
        /* If we have a token stored
        $token = new \League\OAuth2\Client\Token\AccessToken([
            'access_token' => '',
            'refresh_token' => '',
            'expires' => 123456789
        ]);
        */
    }
    function getBynder()
    {
        return $this->bynder;
    }
}
