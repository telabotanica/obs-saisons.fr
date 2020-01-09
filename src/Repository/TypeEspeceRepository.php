<?php

namespace App\Repository;

use App\Entity\TypeEspece;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TypeEspece|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeEspece|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeEspece[]    findAll()
 * @method TypeEspece[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeEspeceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeEspece::class);
    }

    // /**
    //  * @return TypeEspece[] Returns an array of TypeEspece objects
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
    public function findOneBySomeField($value): ?TypeEspece
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
