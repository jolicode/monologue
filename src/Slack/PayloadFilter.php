<?php

namespace App\Slack;

class PayloadFilter
{
    private $channel;

    public function __construct(string $channel)
    {
        $this->channel = $channel;
    }

    public function isNewMessageOrReaction(array $payload): bool
    {
        if ('event_callback' !== $payload['type']) {
            return false;
        }

        $e = $payload['event'];
        if ('message' === $e['type']) {
            if ($this->channel !== $e['channel']) {
                return false;
            }
            // Bot, edit, ...
            if (($e['subtype'] ?? false) && 'me_message' !== $e['subtype'] && 'file_share' !== $e['subtype']) {
                return false;
            }
            // Slack Command
            if ('/' === mb_substr($e['text'], 0, 1)) {
                return false;
            }
        } elseif ('reaction_added' === $e['type']) {
            if ($this->channel !== $e['item']['channel']) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }
}
