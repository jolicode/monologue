<?php

namespace App\ControlTower;

use App\Entity\Amnesty;
use App\Repository\AmnestyRepository;
use App\Repository\DebtRepository;
use Doctrine\ORM\EntityManagerInterface;

class Government
{
    private $amnestyRepository;
    private $debtRepository;
    private $em;

    public function __construct(AmnestyRepository $amnestyRepository, DebtRepository $debtRepository, EntityManagerInterface $em)
    {
        $this->amnestyRepository = $amnestyRepository;
        $this->debtRepository = $debtRepository;
        $this->em = $em;
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
