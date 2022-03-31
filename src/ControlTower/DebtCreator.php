<?php

namespace App\ControlTower;

use App\Entity\Debt;
use App\Entity\Event;
use App\Repository\DebtRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;

class DebtCreator
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventRepository $eventRepository,
        private DebtRepository $debtRepository,
        private string $timezone)
    {
    }

    public function createDebtIfNeeded(array $payload): ?Debt
    {
        $this->em->getConnection()->beginTransaction();
        $tableName = $this->em->getClassMetadata(Event::class)->getTableName();
        $this->em->getConnection()->exec("LOCK $tableName IN ACCESS EXCLUSIVE MODE");

        $event = $this->insertEvent($payload);
        $debt = $this->doCreateDebtIfNeeded($event);

        $this->em->getConnection()->commit();

        return $debt;
    }

    private function insertEvent(array $payload): Event
    {
        $e = $payload['event'];

        if ('message' === $e['type']) {
            $text = $e['text'];
        } elseif ('reaction_added' === $e['type']) {
            $text = $e['reaction'];
        } else {
            throw new \RuntimeException('The type is not supported.');
        }

        $date = new \DateTimeImmutable('@'.$e['event_ts']);
        $date = $date->setTimezone(new \DateTimeZone($this->timezone));
        $event = new Event($e['type'], $text, $e['user'], $date);

        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }

    private function doCreateDebtIfNeeded(Event $event): ?Debt
    {
        $firstMessageOfDay = $this->eventRepository->getFirstMessageOfDay($event);
        if (!$firstMessageOfDay) {
            return null;
        }

        if ($firstMessageOfDay->getAuthor() === $event->getAuthor()) {
            return null;
        }

        $isDebtExist = $this->debtRepository->isDebtExist($event);
        if ($isDebtExist) {
            return null;
        }

        $debt = new Debt($event, $firstMessageOfDay);
        $this->em->persist($debt);
        $this->em->flush();

        return $debt;
    }
}
