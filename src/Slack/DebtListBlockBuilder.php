<?php

namespace App\Slack;

use App\Repository\DebtRepository;

class DebtListBlockBuilder
{
    public function __construct(
        private readonly DebtRepository $debtRepository,
    ) {
    }

    public function buildBlocks(string $usedId): array
    {
        $debts = $this->debtRepository->findPendings();

        if (!$debts) {
            return [
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => '*There are no more debts*',
                    ],
                ],
            ];
        }

        $blocks = [
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '*Pending debts*',
                ],
            ],
            [
                'type' => 'divider',
            ],
        ];

        foreach ($debts as $debt) {
            $event = $debt->getEvent();
            $block = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => \sprintf('<@%s>, %s days ago.', $event->getAuthor(), (new \DateTime())->diff($event->getCreatedAt())->format('%a')),
                ],
            ];

            if ($debt->getAuthor() !== $usedId) {
                $block['accessory'] = [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => 'Mark as paid',
                        'emoji' => true,
                    ],
                    'value' => 'ack-' . $debt->getId(),
                ];
            }

            $blocks[] = $block;
        }

        if (\count($blocks) >= 50) {
            $blocks = \array_slice($blocks, -47);
            $blocks[] = [
                'type' => 'divider',
            ];
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => 'There is more debts, but slack can display only 50. It\'s time to ask for amnesty?',
                ],
            ];
        }

        return $blocks;
    }
}
