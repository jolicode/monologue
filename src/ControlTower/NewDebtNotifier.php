<?php

namespace App\ControlTower;

use App\Entity\Debt;
use App\Slack\MessagePoster;

class NewDebtNotifier
{
    public function __construct(
        private readonly MessagePoster $messagePoster,
    ) {
    }

    public function notifyNewDebt(Debt $debt): void
    {
        $this->messagePoster->postMessage('Fraud detected.', $this->buildBlocks($debt));
    }

    private function buildBlocks(Debt $debt): array
    {
        $event = $debt->getEvent();

        $explanation = '';

        if ('message' === $event->getType()) {
            $explanation = 'message posted';
        } elseif ('reaction_added' === $event->getType()) {
            $explanation = \sprintf('reaction "%s" added', $event->getContent());
        }

        if ($explanation) {
            $explanation = \sprintf(' Reason: %s.', $explanation);
        }

        return [
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => \sprintf('Thanks <@%s> for the next breakfast!%s', $event->getAuthor(), $explanation),
                    ],
                ],
            ],
        ];
    }
}
