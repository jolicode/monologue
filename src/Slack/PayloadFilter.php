<?php

namespace App\Slack;

class PayloadFilter
{
    public function __construct(
        private string $channel,
        private ?array $botIds = null
    ) {
        $this->botIds = $botIds ?? [];
    }

    public function isNewMessageOrReaction(array $payload): bool
    {
        if ('event_callback' !== $payload['type']) {
            return false;
        }

        $e = $payload['event'];

        $userId = $e['user'] ?? null;
        if ($userId && \in_array($userId, $this->botIds, true)) {
            return false;
        }

        if ('message' === $e['type']) {
            if ($this->channel !== $e['channel']) {
                return false;
            }
            // Only handle special messages /me and file share (ignore others like bot message, channel join/leave, message edits, etc)
            if (($e['subtype'] ?? false) && 'me_message' !== $e['subtype'] && 'file_share' !== $e['subtype']) {
                return false;
            }
            // Bot
            if ($e['bot_profile'] ?? false) {
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
