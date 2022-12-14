<?php

namespace App\ControlTower;

use App\Entity\Amnesty;
use App\Repository\AmnestyRepository;
use App\Repository\DebtRepository;
use App\Slack\MessagePoster;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class Government
{
    public function __construct(
        private readonly AmnestyRepository $amnestyRepository,
        private readonly DebtRepository $debtRepository,
        private readonly EntityManagerInterface $em,
        private readonly MessagePoster $messagePoster,
        #[Autowire('%env(AMNESTY_THRESHOLD)%')]
        private readonly int $threshold,
    ) {
    }

    public function redeem(string $userId): void
    {
        $amnesty = $this->amnestyRepository->findOneBy([
            'date' => new \DateTimeImmutable(),
        ]);

        if (!$amnesty) {
            $amnesty = new Amnesty();
            $this->em->persist($amnesty);
        }

        $amnesty->addUserId($userId);

        // Throw an exception if the threshold is reached
        $amnesty->redeem($this->threshold);

        $this->debtRepository->ackAllDebts();

        $this->messagePoster->postMessage('The amnesty has been redeemed. All debts have been wiped. 🎆');
    }
}
