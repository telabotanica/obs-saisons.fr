<?php

namespace App\Repository;

use App\Entity\Individual;
use App\Entity\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Individual|null find($id, $lockMode = null, $lockVersion = null)
 * @method Individual|null findOneBy(array $criteria, array $orderBy = null)
 * @method Individual[]    findAll()
 * @method Individual[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IndividualRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Individual::class);
    }

    public function findSpeciesIndividualsForStation(Station $station): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.species', 'species')
            ->leftJoin('i.station', 'station')
            ->where('station = :station')
            ->setParameter('station', $station)
            ->orderBy('i.species', 'ASC')
            ->getQuery()
            ->getResult()
        ;

    }

    // /**
    //  * @return Individual[] Returns an array of Individual objects
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
    public function findOneBySomeField($value): ?Individual
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
