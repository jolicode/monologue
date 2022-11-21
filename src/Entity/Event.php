<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    #[ORM\Column(type: 'guid')]
    private readonly string $id;

    public function __construct(
        #[ORM\Column(type: 'string', length: 255)]
        private readonly string $type,

        #[ORM\Column(type: 'text')]
        private readonly string $content,

        #[ORM\Column(type: 'string', length: 255)]
        private readonly string $author,

        #[ORM\Column(type: 'datetime_immutable_ms')]
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->id = uuid_create();
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
