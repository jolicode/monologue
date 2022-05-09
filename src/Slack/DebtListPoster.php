<?php

namespace App\Slack;

class DebtListPoster
{
    public function __construct(
        private DebtListBlockBuilder $blockBuilder,
        private MessagePoster $messagePoster
    ) {
    }

    public function postDebtList(string $responseUrl = null)
    {
        $blocks = $this->blockBuilder->buildBlocks();

        $this->messagePoster->postMessage('Dettes en attente.', $blocks, $responseUrl);
    }
}
