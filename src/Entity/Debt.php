<?php

namespace App\Entity;

use App\Repository\DebtRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DebtRepository::class)]
class Debt
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(type: 'guid')]
    private readonly string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private readonly string $author;

    #[ORM\Column(type: 'date_immutable')]
    private readonly \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private \DateTimeImmutable $paidAt;

    #[ORM\Column(type: 'boolean')]
    private bool $paid;

    public function __construct(
        #[ORM\OneToOne(targetEntity: Event::class, cascade: ['persist', 'remove'])]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private readonly Event $event,

        #[ORM\ManyToOne(targetEntity: Event::class, cascade: ['persist', 'remove'])]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private readonly Event $cause,
    ) {
        $this->id = uuid_create();
        $this->author = $event->getAuthor();
        $this->createdAt = new \DateTimeImmutable($event->getCreatedAt()->format('Y-m-d'));
        $this->paid = false;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEvent(): Event
    {
        return $this->event;
    }

    public function getCause(): Event
    {
        return $this->cause;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isPaid(): bool
    {
        return $this->paid;
    }

    public function markAsPaid(): void
    {
        if ($this->paid) {
            throw new \DomainException('The debt is already paid.');
        }

        $this->paid = true;
        $this->paidAt = new \DateTimeImmutable();
    }
}
