<?php

namespace App\ControlTower;

use App\Entity\Amnesty;
use App\Repository\AmnestyRepository;
use App\Repository\DebtRepository;
use Doctrine\ORM\EntityManagerInterface;

class Government
{
    public function __construct(
        private AmnestyRepository $amnestyRepository,
        private DebtRepository $debtRepository,
        private EntityManagerInterface $em)
    {
    }

    public function redeem(string $userId)
    {
        $amnesty = $this->amnestyRepository->findOneBy([
            'date' => new \DateTimeImmutable(),
        ]);

        if (!$amnesty) {
            $amnesty = new Amnesty();
            $this->em->persist($amnesty);
        }

        $amnesty->addUserId($userId);

        // Throw an exception if not possible
        $amnesty->redeem();

        $this->debtRepository->ackAllDebts();
    }
}
