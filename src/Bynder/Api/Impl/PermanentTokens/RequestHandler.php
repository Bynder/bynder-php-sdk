<?php

namespace Bynder\Api\Impl\PermanentTokens;

use Guzzle;

use Bynder\Api\Impl\AbstractRequestHandler;

class RequestHandler extends AbstractRequestHandler
{
    protected $httpClient;

    public function __construct($configuration)
    {
        $this->configuration = $configuration;
        $this->httpClient = new \GuzzleHttp\Client();
    }

    protected function sendAuthenticatedRequest($requestMethod, $uri, $options = [])
    {
        $request = new \GuzzleHttp\Psr7\Request($requestMethod, $uri, [
            'Authorization' => 'Bearer ' . $this->configuration->getToken(),
        ]);

        return $this->httpClient->sendAsync($request, $this->getRequestOptions($options));
    }
}
