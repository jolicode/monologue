<?php

namespace App\Slack;

use App\Repository\DebtRepository;

class DebtListBlockBuilder
{
    public function __construct(
        private MessagePoster $messagePoster,
        private DebtRepository $debtRepository
    ) {
    }

    public function buildBlocks(): array
    {
        $debts = $this->debtRepository->findPendings();

        if (!$debts) {
            return [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => '*Il n\'y a plus de dettes*',
                    ],
                ],
            ];
        }

        $blocks = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '*Dettes en cours*',
                ],
            ],
            [
                'type' => 'divider',
            ],
        ];

        foreach ($debts as $debt) {
            $event = $debt->getEvent();
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => sprintf('<@%s>, depuis %s jours.', $event->getAuthor(), (new \DateTime())->diff($event->getCreatedAt())->format('%a')),
                ],
                'accessory' => [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => 'Marquer comme payée',
                        'emoji' => true,
                    ],
                    'value' => 'ack-' . $debt->getId(),
                ],
            ];
        }

        if (\count($blocks) >= 50) {
            $blocks = \array_slice($blocks, 47);
            $blocks[] = [
                'type' => 'divider',
            ];
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => 'Il y a plus de dettes, mais slack ne peut en afficher que 50. Il est temps de demander une amnistie?',
                ],
            ];
        }

        return $blocks;
    }
}
