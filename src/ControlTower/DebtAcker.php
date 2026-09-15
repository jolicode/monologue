<?php

namespace App\ControlTower;

use App\Entity\Debt;
use App\Repository\DebtRepository;
use App\Util\Uuid;

class DebtAcker
{
    public function __construct(
        private readonly DebtRepository $debtRepository,
    ) {
    }

    public function ackDebt(array $payload, string $debtId): Debt
    {
        if (!Uuid::isValidV4($debtId)) {
            throw new \DomainException('The UUID is not valid.');
        }

        $debt = $this->debtRepository->find($debtId);
        if (!$debt) {
            throw new \DomainException('There are not debt with this UUID.');
        }

        if ($debt->getAuthor() === $payload['user']['id']) {
            throw new \DomainException('You can not ACK your own debt.');
        }

        $debt->markAsPaid();

        return $debt;
    }

    /**
     * @return non-empty-list<Debt>
     */
    public function ackAllDebts(array $payload, string $author): array
    {
        if ($author === $payload['user']['id']) {
            throw new \DomainException('You can not ACK your own debts.');
        }

        $debts = $this->debtRepository->findPendingsByAuthor($author);
        if (!$debts) {
            throw new \DomainException('There are no pending debts for this user.');
        }

        foreach ($debts as $debt) {
            $debt->markAsPaid();
        }

        return $debts;
    }
}
