<?php

namespace App\Repository;

use App\Entity\Debt;
use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Debt>
 *
 * @method Debt|null find($id, $lockMode = null, $lockVersion = null)
 * @method Debt|null findOneBy(array $criteria, array $orderBy = null)
 * @method Debt[]    findAll()
 * @method Debt[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DebtRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Debt::class);
    }

    public function isDebtExist(Event $event): bool
    {
        return (bool) $this
            ->createQueryBuilder('d')
            ->select('COUNT(1)')
            ->andWhere('d.author = :author')->setParameter('author', $event->getAuthor())
            ->andWhere('d.createdAt = :day')->setParameter('day', $event->getCreatedAt()->format('Y-m-d'))
            ->getQuery()
            ->execute(null, Query::HYDRATE_SINGLE_SCALAR)
        ;
    }

    /**
     * @return list<Debt>
     */
    public function findPendings(): array
    {
        return $this
            ->createQueryBuilder('d')
            ->andWhere('d.paid = :paid')->setParameter('paid', false)
            ->addOrderBy('d.createdAt', 'ASC')
            ->addOrderBy('d.author', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Authors are sorted by their oldest pending debt, and the debts of each
     * author are sorted from the oldest to the newest.
     *
     * @return array<string, non-empty-list<Debt>>
     */
    public function findPendingsGroupedByAuthor(): array
    {
        $debtsByAuthor = [];

        foreach ($this->findPendings() as $debt) {
            $debtsByAuthor[$debt->getAuthor()][] = $debt;
        }

        return $debtsByAuthor;
    }

    /**
     * @return list<Debt>
     */
    public function findPendingsByAuthor(string $author): array
    {
        return $this
            ->createQueryBuilder('d')
            ->andWhere('d.paid = :paid')->setParameter('paid', false)
            ->andWhere('d.author = :author')->setParameter('author', $author)
            ->addOrderBy('d.createdAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function ackAllDebts(): void
    {
        $this
            ->createQueryBuilder('d')
            ->update()
            ->andWhere('d.paid = :paid')->setParameter('paid', false)
            ->set('d.paid', ':newPaid')->setParameter('newPaid', true)
            ->set('d.paidAt', ':newPaidAt')->setParameter('newPaidAt', new \DateTimeImmutable())
            ->getQuery()
            ->execute()
        ;
    }
}
