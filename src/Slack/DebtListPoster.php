<?php

namespace App\Slack;

class DebtListPoster
{
    private $blockBuilder;
    private $messagePoster;

    public function __construct(DebtListBlockBuilder $blockBuilder, MessagePoster $messagePoster)
    {
        $this->blockBuilder = $blockBuilder;
        $this->messagePoster = $messagePoster;
    }

    public function postDebtList(string $responseUrl = null)
    {
        $blocks = $this->blockBuilder->buildBlocks();

        $this->messagePoster->postMessage('Pending debts.', $blocks, $responseUrl);
    }
}
