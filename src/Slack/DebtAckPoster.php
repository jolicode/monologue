<?php

namespace App\Slack;

use App\Entity\Debt;

class DebtAckPoster
{
    public function __construct(
        private readonly MessagePoster $messagePoster,
    ) {
    }

    /**
     * @param non-empty-list<Debt> $debts All the debts must belong to the same author
     */
    public function postDebtsAck(array $debts, string $user): void
    {
        $author = $debts[0]->getAuthor();
        $count = \count($debts);

        if (1 === $count) {
            $message = \sprintf("<@%s>'s debt was marked as paid by <@%s> !", $author, $user);
        } else {
            $message = \sprintf("<@%s>'s %d debts were marked as paid by <@%s> !", $author, $count, $user);
        }

        $this->messagePoster->postMessage($message);
    }
}
