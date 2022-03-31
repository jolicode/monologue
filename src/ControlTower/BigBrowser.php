<?php

namespace App\ControlTower;

use App\Entity\Debt;
use App\Slack\PayloadFilter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class BigBrowser
{
    public function __construct(
        private PayloadFilter $payloadFilter,
        private DebtCreator $debtCreator,
        private NewDebtNotifier $newDebtNotifier,
        private ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
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
            $this->newDebtNotifier->notifiyNewDebt($debt);

            $this->logger->notice('A new debt has been created', [
                'debt' => $debt,
            ]);
        }

        return $debt;
    }
}
