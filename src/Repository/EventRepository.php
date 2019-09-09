<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function getFirstMessageOfDay(Event $event): ?Event
    {
        return $this
            ->createQueryBuilder('e')
            ->andWhere('e.createdAt >= :day')->setParameter('day', $event->getCreatedAt()->format('Y-m-d'))
            ->andWhere('e.createdAt < :eventDate')->setParameter('eventDate', $event->getCreatedAt()->format('Y-m-d H:i:s.v'))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByCreatedAt(\DateTimeImmutable $date): array
    {
        $start = $date->format('Y-m-d');
        $end = $date->modify('+1 day')->format('Y-m-d');

        return $this
            ->createQueryBuilder('e')
            ->andWhere('e.createdAt >= :start')->setParameter('start', $start)
            ->andWhere('e.createdAt < :end')->setParameter('end', $end)
            ->addOrderBy('e.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
