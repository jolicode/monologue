<?php

namespace App\Slack;

use App\Entity\Debt;
use App\Repository\DebtRepository;

class DebtListBlockBuilder
{
    /**
     * Slack refuses messages with more than 50 blocks.
     */
    private const int MAX_BLOCKS = 50;

    public function __construct(
        private readonly DebtRepository $debtRepository,
    ) {
    }

    public function buildBlocks(string $userId): array
    {
        $debtsByAuthor = $this->debtRepository->findPendingsGroupedByAuthor();

        if (!$debtsByAuthor) {
            return [
                $this->section('*There are no more debts*'),
            ];
        }

        $blocks = [
            $this->section('*Pending debts*'),
            $this->divider(),
        ];
        $footer = [
            $this->divider(),
            $this->section('There are more debtors, but Slack can display only 50 blocks. It\'s time to ask for amnesty?'),
        ];

        $now = new \DateTimeImmutable();
        $lastAuthor = array_key_last($debtsByAuthor);

        foreach ($debtsByAuthor as $author => $debts) {
            $authorBlocks = $this->buildAuthorBlocks($author, $debts, $userId, $now);
            // Keep room for the footer, unless this is the last author
            $reserved = $author === $lastAuthor ? 0 : \count($footer);

            if (\count($blocks) + \count($authorBlocks) + $reserved > self::MAX_BLOCKS) {
                return [...$blocks, ...$footer];
            }

            $blocks = [...$blocks, ...$authorBlocks];
        }

        return $blocks;
    }

    /**
     * @param non-empty-list<Debt> $debts Sorted from the oldest to the newest
     */
    private function buildAuthorBlocks(string $author, array $debts, string $userId, \DateTimeImmutable $now): array
    {
        $count = \count($debts);
        $ages = array_map(fn (Debt $debt) => $this->formatAge($debt, $now), $debts);

        $blocks = [
            $this->section(\sprintf(
                '<@%s>, %d %s: %s.',
                $author,
                $count,
                1 === $count ? 'debt' : 'debts',
                implode(', ', $ages),
            )),
        ];

        // One can not ACK its own debts
        if ($author === $userId) {
            return $blocks;
        }

        $oldest = $debts[0];

        if (1 === $count) {
            $buttons = [
                $this->button('Mark as paid', 'mark-first-as-paid', 'ack-' . $oldest->getId()),
            ];
        } else {
            $buttons = [
                $this->button('Mark first as paid', 'mark-first-as-paid', 'ack-' . $oldest->getId()),
                $this->button('Mark all as paid', 'mark-all-as-paid', 'ack-all-' . $author),
            ];
        }

        $blocks[] = [
            'type' => 'actions',
            'elements' => $buttons,
        ];

        return $blocks;
    }

    private function formatAge(Debt $debt, \DateTimeImmutable $now): string
    {
        $days = (int) $now->diff($debt->getEvent()->getCreatedAt())->format('%a');

        return match ($days) {
            0 => 'today',
            1 => '1 day ago',
            default => \sprintf('%d days ago', $days),
        };
    }

    private function section(string $text): array
    {
        return [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => $text,
            ],
        ];
    }

    private function divider(): array
    {
        return [
            'type' => 'divider',
        ];
    }

    private function button(string $text, string $actionId, string $value): array
    {
        return [
            'type' => 'button',
            'text' => [
                'type' => 'plain_text',
                'text' => $text,
                'emoji' => true,
            ],
            'action_id' => $actionId,
            'value' => $value,
        ];
    }
}
