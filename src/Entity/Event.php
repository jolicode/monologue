<?php

namespace App\Entity;

use App\Repository\EventRepository;
use App\Util\UuidGenerator;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'string', length: 255)]
    private string $author;

    #[ORM\Column(type: 'datetime_immutable_ms')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $type, string $content, string $author, \DateTimeImmutable $createdAt)
    {
        $this->id = UuidGenerator::v4();
        $this->type = $type;
        $this->content = $content;
        $this->author = $author;
        $this->createdAt = $createdAt;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
