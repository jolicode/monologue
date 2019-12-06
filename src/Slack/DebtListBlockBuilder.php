<?php

namespace App\Slack;

use App\Repository\DebtRepository;

class DebtListBlockBuilder
{
    private $messagePoster;
    private $debtRepository;

    public function __construct(MessagePoster $messagePoster, DebtRepository $debtRepository)
    {
        $this->messagePoster = $messagePoster;
        $this->debtRepository = $debtRepository;
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
                    'value' => 'ack-'.$debt->getId(),
                ],
            ];
        }

        return $blocks;
    }
}
