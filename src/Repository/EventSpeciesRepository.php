<?php

namespace App\Repository;

use App\Entity\EventSpecies;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method EventSpecies|null find($id, $lockMode = null, $lockVersion = null)
 * @method EventSpecies|null findOneBy(array $criteria, array $orderBy = null)
 * @method EventSpecies[]    findAll()
 * @method EventSpecies[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventSpeciesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventSpecies::class);
    }

    // /**
    //  * @return EventSpecies[] Returns an array of EventSpecies objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EventSpecies
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
