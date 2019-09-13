<?php

namespace App\ControlTower;

use App\Entity\Debt;
use App\Slack\MessagePoster;

class NewDebtNotifier
{
    private $messagePoster;

    public function __construct(MessagePoster $messagePoster)
    {
        $this->messagePoster = $messagePoster;
    }

    public function notifiyNewDebt(Debt $debt)
    {
        $this->messagePoster->postMessage('Fraude détectée', $this->buildBlocks($debt));
    }

    private function buildBlocks(Debt $debt): array
    {
        $event = $debt->getEvent();

        $explanation = '';

        if ('message' === $event->getType()) {
            $explanation = 'message posté';
        } elseif ('reaction_added' === $event->getType()) {
            $explanation = sprintf('réaction "%s" ajoutée', $event->getContent());
        }

        if ($explanation) {
            $explanation = sprintf(' Raison : %s.', $explanation);
        }

        $blocks = [
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => sprintf('Merci <@%s> pour le prochain petit dej !%s', $event->getAuthor(), $explanation),
                    ],
                ],
            ],
        ];

        return $blocks;
    }
}
