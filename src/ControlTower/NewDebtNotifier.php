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
        $this->messagePoster->postMessage($this->buildText($debt), $this->buildBlocks($debt));
    }

    private function buildText(Debt $debt): string
    {
        $event = $debt->getEvent();

        if ('message' === $event->getType()) {
            return sprintf("<@%s> a laissé un message: \n>>>%s", $event->getAuthor(), $event->getContent());
        }

        if ('reaction_added' === $event->getType()) {
            return sprintf("<@%s> réagit à un message: \n>>>%s", $event->getAuthor(), $event->getContent());
        }

        throw new \RuntimeException('The type is not supported.');
    }

    private function buildBlocks(Debt $debt): array
    {
        $event = $debt->getEvent();
        $blocks = [
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => sprintf('Merci <@%s> pour le prochain petit dej!', $event->getAuthor()),
                    ],
                ],
            ],
        ];

        return $blocks;
    }
}
