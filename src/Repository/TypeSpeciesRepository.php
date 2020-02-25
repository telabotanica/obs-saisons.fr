<?php

namespace App\Repository;

use App\Entity\TypeSpecies;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TypeSpecies|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeSpecies|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeSpecies[]    findAll()
 * @method TypeSpecies[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeSpeciesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeSpecies::class);
    }

    // /**
    //  * @return TypeSpecies[] Returns an array of TypeSpecies objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TypeSpecies
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
