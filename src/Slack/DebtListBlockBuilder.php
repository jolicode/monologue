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
                        'text' => '*There are no pending depts*',
                    ],
                ],
            ];
        }

        $blocks = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '*Find bellow the pending debs*',
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
                    'text' => sprintf('<@%s> on %s', $event->getAuthor(), $event->getCreatedAt()->format('Y-m-d')),
                ],
                'accessory' => [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => 'ACK',
                        'emoji' => true,
                    ],
                    'value' => 'ack-'.$debt->getId(),
                ],
            ];
        }

        return $blocks;
    }
}
