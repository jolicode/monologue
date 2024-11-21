<?php

namespace App\Slack;

class DebtListPoster
{
    public function __construct(
        private readonly DebtListBlockBuilder $blockBuilder,
        private readonly MessagePoster $messagePoster,
    ) {
    }

    public function postDebtList(string $userId, ?string $responseUrl = null): void
    {
        $blocks = $this->blockBuilder->buildBlocks($userId);

        $this->messagePoster->postMessage('Pending debts.', $blocks, $responseUrl);
    }
}
