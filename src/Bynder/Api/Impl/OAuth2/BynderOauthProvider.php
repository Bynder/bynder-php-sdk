<?php
namespace Bynder\Api\Impl\OAuth2;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class BynderOauthProvider extends AbstractProvider
{
    use BearerAuthorizationTrait;

    const CODE_CHALLENGE_METHOD = 'S256';
    const CODE_CHALLENGE_METHOD_HASH = 'sha256';
    const CODE_VERIFIER_LENGTH = 43;
    const OAUTH_GRANT_TYPE = 'authorization_code';

    private $codeVerifier;

    protected $bynderDomain;

    public function __construct(array $options = [], array $collaborators = [])
    {
        $this->bynderDomain = 'https://' . $options['bynderDomain'];
        unset($options['bynderDomain']);

        return parent::__construct($options, $collaborators);
    }

    /**
     * Get provider url to run authorization
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->bynderDomain . '/v6/authentication/oauth2/auth';
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * @param array $params
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->bynderDomain . '/v6/authentication/oauth2/token';
    }

    /**
     * Returns the Oauth code verifier string, used to confirm the request.
     *
     * @return string
     */
    public function getCodeVerifier()
    {
        return $this->codeVerifier;
    }

    /**
     * Sets the Oauth code verifier, used to confirm the request.
     *
     * @param string $codeVerifier
     */
    public function setCodeVerifier($codeVerifier)
    {
        $this->codeVerifier = $codeVerifier;
    }

    /**
     * Builds the authorization URL with code challenge and state.
     *
     * @param array $options
     * @return string
     */
    public function getAuthorizationUrl(array $options = [])
    {
        $this->codeVerifier = $this->generate_random_code_verifier();
        $codeChallenge = $this->generate_code_challenge($this->codeVerifier);
        $options = [
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => self::CODE_CHALLENGE_METHOD,
            'scope' => isset($options['scope']) ? $options['scope'] : $this->getDefaultScopes(),
            'response_type' => 'code',
            'state' => isset($options['state']) ? $options['state'] : $this->generate_random_code_verifier(),
        ];
        return parent::getAuthorizationUrl($options);
    }

    /**
     * Get an access token
     *
     * @param string $grant
     * @param array $options
     * @return string
     */
    public function getAccessToken($grant, array $options = [])
    {
        if(isset($this->codeVerifier)) {
            $options['code_verifier'] = $this->codeVerifier;
        }

        return parent::getAccessToken($grant, $options);
    }

    /**
     * Generates a random code verifier string.
     *
     * @return string
     */
    private static function generate_random_code_verifier()
    {
        $random = bin2hex(openssl_random_pseudo_bytes(self::CODE_VERIFIER_LENGTH));
        return self::base64url_encode(pack('H*', $random));
    }

    /**
     * Encodes a string in a special base64 format for Oauth code challenge standards.
     *
     * @param $plainText
     * @return string
     */
    private static function base64url_encode($plainText)
    {
        $base64 = base64_encode($plainText);
        $base64 = trim($base64, "=");
        $base64url = strtr($base64, '+/', '-_');
        return ($base64url);
    }

    /**
     * Generates the Oauth code challenge string.
     *
     * @param $codeVerifier
     * @return string
     */
    private static function generate_code_challenge($codeVerifier)
    {
        return self::base64url_encode(pack('H*', hash(self::CODE_CHALLENGE_METHOD_HASH, $codeVerifier)));
    }

    /**
     * Get the default scopes used by this provider.
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return '';
    }

    /**
     * Check a provider response for errors.
     *
     * @param ResponseInterface $response
     * @param array|string $data
     *
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw new IdentityProviderException(
                $data['error'] ?: $response->getReasonPhrase(),
                $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * Returns the URL for requesting the resource owner's details.
     * Function is necessary since it's abstract in the parent.
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return parent::getResourceOwnerDetailsUrl($token);
    }
    /**
     * Generates a resource owner object from a successful resource
     * owner details request. Function is necessary since it's abstract
     * in the parent.
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return parent::createResourceOwner($response, $token);
    }
}
