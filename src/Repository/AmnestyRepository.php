<?php

namespace App\Repository;

use App\Entity\Amnesty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Amnesty|null find($id, $lockMode = null, $lockVersion = null)
 * @method Amnesty|null findOneBy(array $criteria, array $orderBy = null)
 * @method Amnesty[]    findAll()
 * @method Amnesty[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AmnestyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Amnesty::class);
    }
}
