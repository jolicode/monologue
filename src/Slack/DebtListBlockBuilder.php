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
                    'text' => '*Current debts*',
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
                    'text' => sprintf('<@%s>, since %s days.', $event->getAuthor(), (new \DateTime())->diff($event->getCreatedAt())->format('%a')),
                ],
                'accessory' => [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => 'Mark as paid',
                        'emoji' => true,
                    ],
                    'value' => 'ack-'.$debt->getId(),
                ],
            ];
        }

        return $blocks;
    }
}
