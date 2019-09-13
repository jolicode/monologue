<?php

namespace App\Slack;

use App\Entity\Debt;

class DebtAckPoster
{
    private $messagePoster;

    public function __construct(MessagePoster $messagePoster)
    {
        $this->messagePoster = $messagePoster;
    }

    public function postDebtAck(Debt $debt, string $user)
    {
        $message = sprintf("La dette de <@%s> vient d'etre marquée comme payé par <@%s> !", $debt->getAuthor(), $user);

        $this->messagePoster->postMessage($message);
    }
}
