<?php

namespace App\Repository;

use App\Entity\Individu;
use App\Entity\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Individu|null find($id, $lockMode = null, $lockVersion = null)
 * @method Individu|null findOneBy(array $criteria, array $orderBy = null)
 * @method Individu[]    findAll()
 * @method Individu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IndividuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Individu::class);
    }

    public function findEspecesIndividusForStation(Station $station): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.espece', 'espece')
            ->leftJoin('i.station', 'station')
            ->where('station = :station')
            ->setParameter('station', $station)
            ->orderBy('i.espece', 'ASC')
            ->getQuery()
            ->getResult()
        ;

    }

    // /**
    //  * @return Individu[] Returns an array of Individu objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Individu
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
