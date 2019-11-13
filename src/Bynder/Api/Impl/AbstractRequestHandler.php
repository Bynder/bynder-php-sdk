<?php
namespace Bynder\Api\Impl;

abstract class AbstractRequestHandler
{
    protected $configuration;

    public function sendRequestAsync($requestMethod, $uri, $options = [])
    {
        $uri = sprintf(
            'https://%s/%s', $this->configuration->getBynderDomain(), $uri
        );

        if (!in_array($requestMethod, ['GET', 'POST', 'DELETE'])) {
            throw new Exception('Invalid request method provided');
        }

        $request = $this->sendAuthenticatedRequest($requestMethod, $uri, $options);

        return $request->then(
            function ($response) {
                $mimeType = explode(';', $response->getHeader('Content-Type')[0])[0];
                switch($mimeType) {
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

    abstract protected function sendAuthenticatedRequest($requestMethod, $uri, $options = []);
}