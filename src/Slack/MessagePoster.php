<?php

namespace App\Slack;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MessagePoster
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[Autowire('%env(SLACK_TOKEN)%')]
        private readonly string $token,
        #[Autowire('%env(SLACK_CHANNEL)%')]
        private readonly string $channel,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function postMessage(string $text, array $blocks = [], string $responseUrl = null): void
    {
        $headers = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
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
