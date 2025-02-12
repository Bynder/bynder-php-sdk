<?php

namespace Bynder\Api\Impl\PermanentTokens;

use Guzzle;

use Bynder\Api\Impl\AbstractRequestHandler;

class RequestHandler extends AbstractRequestHandler
{
    protected $configuration;
    protected $httpClient;

    public function __construct($configuration)
    {
        $this->configuration = $configuration;
        $this->httpClient = new \GuzzleHttp\Client();
    }

    protected function sendAuthenticatedRequest($requestMethod, $uri, $options = [])
    {
        $formParams = false;
        if (isset($options['form_params'])) {
            $formParams = $options['form_params'];
            unset($options['form_params']);
        }

        $request = new \GuzzleHttp\Psr7\Request($requestMethod, $uri, $options);

        if ($formParams) {
            $options['form_params'] = $formParams;
        }

        return $this->httpClient->sendAsync(
            $request,
            array_merge(
                $options,
                $this->configuration->getRequestOptions(),
                ['headers'=> [
                    'User-Agent' => 'bynder-php-sdk/' . $this->configuration->getSdkVersion(),
                    'Authorization' => 'Bearer ' . $this->configuration->getToken()
                ]]
            )
        );
    }
}
