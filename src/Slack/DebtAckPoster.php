<?php

namespace App\Slack;

use App\Entity\Debt;

class DebtAckPoster
{
    public function __construct(
        private readonly MessagePoster $messagePoster,
    ) {
    }

    public function postDebtAck(Debt $debt, string $user): void
    {
        $message = \sprintf("<@%s>'s debt was marked as paid by <@%s> !", $debt->getAuthor(), $user);

        $this->messagePoster->postMessage($message);
    }
}
