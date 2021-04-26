<?php

namespace App\Slack;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MessagePoster
{
    private $httpClient;
    private $token;
    private $channel;
    private $logger;

    public function __construct(HttpClientInterface $httpClient, string $token, string $channel, LoggerInterface $logger = null)
    {
        $this->httpClient = $httpClient;
        $this->token = $token;
        $this->channel = $channel;
        $this->logger = $logger ?: new NullLogger();
    }

    public function postMessage(string $text, array $blocks = [], string $responseUrl = null)
    {
        $headers = [
            'headers' => [
                'Authorization' => 'Bearer '.$this->token,
            ],
            'json' => [
                'channel' => $this->channel,
                'text' => $text,
                'blocks' => $blocks,
            ],
        ];

        $url = $responseUrl ?: 'https://slack.com/api/chat.postMessage';

        $response = $this->httpClient->request('POST', $url, $headers);

        if (200 !== $response->getStatusCode()) {
            $this->logger->error('Posting message to slack failed.', [
                'response' => $response,
                'text' => $text,
            ]);

            throw new \RuntimeException('Posting message to slack failed.');
        }

        if (!$response->toArray()['ok']) {
            $this->logger->error('Posting message to slack failed.', [
                'response' => $response,
                'responseDecoded' => $response->toArray(),
                'text' => $text,
            ]);

            throw new \RuntimeException('Posting message to slack failed.');
        }
    }
}
