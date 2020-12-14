<?php

namespace Bynder\Api\Impl;

abstract class AbstractRequestHandler
{
    protected $configuration;

    public function formRequest($uri, $requestMethod)
    {
        if (!in_array($requestMethod, ['GET', 'POST', 'DELETE'])) {
            throw new Exception('Invalid request method provided');
        }

        return sprintf(
            'https://%s/%s',
            $this->configuration->getBynderDomain(),
            $uri
        );
    }

    public function sendRequestAsync($requestMethod, $uri, $options = [])
    {
        $composedUri = $this->formRequest($uri, $requestMethod);
        $request = $this->sendAuthenticatedRequest($requestMethod, $composedUri, $options);

        return $request->then(
            function ($response) {
                $mimeType = explode(';', $response->getHeader('Content-Type')[0])[0];
                switch ($mimeType) {
                    case 'application/json':
                        return json_decode($response->getBody(), true);
                    case 'text/plain':
                        return (string)$response->getBody();
                    case 'text/html':
                        return $response;
                    default:
                        throw new Exception('The response type not recognized.');
                }
            }
        );
    }


    public function sendRequestAsyncRawResponse($requestMethod, $uri, $options = [])
    {
        $composedUri = $this->formRequest($uri, $requestMethod);
        return $this->sendAuthenticatedRequest($requestMethod, $composedUri, $options);
    }

    abstract protected function sendAuthenticatedRequest($requestMethod, $uri, $options = []);
}
