<?php

namespace App\Entity;

use App\Util\UuidGenerator;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity(repositoryClass="App\Repository\EventRepository") */
class Event
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\Column(type="guid")
     */
    private $id;

    /** @ORM\Column(type="string", length=255) */
    private $type;

    /** @ORM\Column(type="text") */
    private $content;

    /** @ORM\Column(type="string", length=255) */
    private $author;

    /** @ORM\Column(type="datetime_immutable_ms")*/
    private $createdAt;

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
