<?php

namespace App\Entity;

use App\Repository\AmnestyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AmnestyRepository::class)]
class Amnesty
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(type: 'guid')]
    private readonly string $id;

    #[ORM\Column(type: 'date_immutable')]
    private readonly \DateTimeImmutable $date;

    #[ORM\Column(type: 'json')]
    private array $userIds;

    #[ORM\Column(type: 'boolean')]
    private bool $redeemed;

    public function __construct()
    {
        $this->id = uuid_create();
        $this->date = new \DateTimeImmutable();
        $this->userIds = [];
        $this->redeemed = false;
    }

    public function getId(): string
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

    public function redeem(int $threshold): void
    {
        if ($this->redeemed) {
            throw new \DomainException('The Amnesty has already been redeemed.');
        }

        if (($c = \count($this->userIds)) < $threshold) {
            throw new \DomainException(sprintf('More people need to ask for amnesty to complete it! (%d/%d)', $c, $threshold));
        }

        $this->redeemed = true;
    }
}
