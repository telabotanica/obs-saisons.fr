<?php

namespace App\Repository;

use App\Entity\EvenementEspece;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method EvenementEspece|null find($id, $lockMode = null, $lockVersion = null)
 * @method EvenementEspece|null findOneBy(array $criteria, array $orderBy = null)
 * @method EvenementEspece[]    findAll()
 * @method EvenementEspece[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvenementEspeceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvenementEspece::class);
    }

    // /**
    //  * @return EvenementEspece[] Returns an array of EvenementEspece objects
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
    public function findOneBySomeField($value): ?EvenementEspece
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
