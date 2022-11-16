<?php

namespace App\ControlTower;

use App\Entity\Debt;
use App\Slack\PayloadFilter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class BigBrowser
{
    public function __construct(
        private readonly PayloadFilter $payloadFilter,
        private readonly DebtCreator $debtCreator,
        private readonly NewDebtNotifier $newDebtNotifier,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function control(array $payload): ?Debt
    {
        $this->logger->info('New payload.', [
            'payload' => $payload,
        ]);

        if (!$this->payloadFilter->isNewMessageOrReaction($payload)) {
            $this->logger->debug('Discard the payload.');

            return null;
        }

        $debt = $this->debtCreator->createDebtIfNeeded($payload);

        if ($debt) {
            $this->newDebtNotifier->notifyNewDebt($debt);

            $this->logger->notice('A new debt has been created.', [
                'debt' => $debt,
            ]);
        }

        return $debt;
    }
}
