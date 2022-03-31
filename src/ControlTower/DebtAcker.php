<?php

namespace App\ControlTower;

use App\Entity\Debt;
use App\Repository\DebtRepository;
use App\Util\UuidGenerator;

class DebtAcker
{
    public function __construct(private DebtRepository $debtRepository)
    {
    }

    public function ackDebt(array $payload, string $debtId): Debt
    {
        if (!UuidGenerator::isValidV4($debtId)) {
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
}
