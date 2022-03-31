<?php

namespace App\Entity;

use App\Util\UuidGenerator;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DebtRepository")
 */
class Debt
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="guid")
     */
    private string $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Event", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private Event $event;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Event", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private Event $cause;

    /** @ORM\Column(type="string", length=255) */
    private string $author;

    /** @ORM\Column(type="date_immutable") */
    private \DateTimeImmutable $createdAt;

    /** @ORM\Column(type="date_immutable", nullable=true) */
    private \DateTimeImmutable $paidAt;

    /** @ORM\Column(type="boolean") */
    private bool $paid;

    public function __construct(Event $event, Event $cause)
    {
        $this->id = UuidGenerator::v4();
        $this->event = $event;
        $this->cause = $cause;
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

    public function markAsPaid()
    {
        $this->paid = true;
        $this->paidAt = new \DateTimeImmutable();
    }
}
