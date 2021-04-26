<?php

namespace App\Entity;

use App\Repository\AmnestyRepository;
use App\Util\UuidGenerator;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=AmnestyRepository::class)
 */
class Amnesty
{
    private const THRESHOLD = 2;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="guid")
     */
    private $id;

    /**
     * @ORM\Column(type="date_immutable")
     */
    private $date;

    /**
     * @ORM\Column(type="json")
     */
    private $userIds;

    /**
     * @ORM\Column(type="boolean")
     */
    private $redeemed;

    public function __construct()
    {
        $this->id = UuidGenerator::v4();
        $this->date = new \DateTimeImmutable();
        $this->userIds = [];
        $this->redeemed = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getUserIds(): ?array
    {
        return $this->userIds;
    }

    public function addUserId(string $userIds): void
    {
        $this->userIds[$userIds] = true;
    }

    public function redeem(): void
    {
        if ($this->redeemed) {
            throw new \DomainException('The Amnesty a déjà été effectuée.');
        }

        if (\count($this->userIds) < self::THRESHOLD) {
            throw new \DomainException("Il n'y pas encore assez de demander pour effectuer l'amnistie.");
        }

        $this->redeemed = true;
    }
}
