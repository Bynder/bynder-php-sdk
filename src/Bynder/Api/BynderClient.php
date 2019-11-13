<?php
namespace Bynder\Api;

use Bynder\Api\Impl\AssetBankManager;
use Bynder\Api\Impl\OAuth2;
use Bynder\Api\Impl\PermanentTokens;

class BynderClient
{
    /**
     * @var RequestHandler used to process API requests.
     */
    private $requestHandler;

    /**
     * @var AssetBankManager instance used for all Asset related operations.
     */
    private $assetBankManager;

    public function __construct($configuration)
    {
        if ($configuration instanceof PermanentTokens\Configuration) {
            $this->requestHandler = new PermanentTokens\RequestHandler($configuration);
        } else if($configuration instanceof OAuth2\Configuration) {
            $this->requestHandler = new OAuth2\RequestHandler($configuration);
        } else {
            throw new \Exception('Invalid configuration passed');
        }

        $this->configuration = $configuration;
    }

    /**
     * Gets an instance of the asset bank manager to use for DAM queries.
     *
     * @return AssetBankManager An instance of the asset bank manager using the request handler previously created.
     */
    public function getAssetBankManager()
    {
        if(!isset($this->assetBankManager)) {
            $this->assetBankManager = new AssetBankManager($this->requestHandler);
        }

        return $this->assetBankManager;
    }

    /**
     * Returns the Oauth authorization url for user login.
     *
     * @param array $scope Custom scopes can be passed to override the defaults
     * @param string $state Custom state can be passed to override the default random generation
     *
     * @return string
     */
    public function getAuthorizationUrl(array $scope, $state = null)
    {
        return $this->requestHandler->getAuthorizationUrl([
            'state' => $state,
            'scope' => implode(' ', $scope)
        ]);
    }

    /**
     * Returns the Oauth access token.
     *
     * @param $code
     * @return \League\OAuth2\Client\Token\AccessToken
     */
    public function getAccessToken($code)
    {
        $token = $this->requestHandler->getAccessToken($code);
        $this->configuration->setToken($token);

        return $token;
    }

    /**
     * Retrieve all users.
     *
     * @param bool $includeInactive Include inactive users.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function getUsers($includeInactive = false)
    {
        if ($includeInactive) {
            $inactive = '1';
        }
        else {
            $inactive = '0';
        }
        return $this->requestHandler->sendRequestAsync('GET', "api/v4/users/?includeInActive=$inactive");
    }

    /**
     * Retrieve all users or specific ones by ID.
     *
     * @param $userId
     * @param $query
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function getUser($userId = '', $query = null)
    {
        return $this->requestHandler->sendRequestAsync('GET', "api/v4/users/$userId",
            [
                'query' => $query
            ]
        );
    }

    /**
     * Retrieve current user.
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function getCurrentUser()
    {
        return $this->requestHandler->sendRequestAsync('GET', "api/v4/currentUser/");
    }

    /**
     * Retrieve all security profiles or specific ones by ID.
     *
     * @param $profileId
     *
     * @return \GuzzleHttp\Promise\PromiseInterface
     * @throws \Exception
     */
    public function getSecurityProfile($profileId = '')
    {
        return $this->requestHandler->sendRequestAsync('GET', "api/v4/profiles/$profileId");
    }
}
