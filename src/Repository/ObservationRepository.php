<?php

namespace App\Repository;

use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Observation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Observation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Observation[]    findAll()
 * @method Observation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ObservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Observation::class);
    }

    public function findAllObservationsInStation(Station $station, array $stationIndividuals = null): array
    {
        $stationIndividuals = $stationIndividuals ?? $this->getEntityManager()
            ->getRepository(Individual::class)
            ->findBy(['station' => $station])
        ;

        return $this->createQueryBuilder('o')
            ->where('o.individual IN (:individuals)')
            ->setParameter('individuals', $stationIndividuals)
            ->addOrderBy('o.individual', 'ASC')
            ->addOrderBy('o.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findLastObs(int $limit): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.is_missing = 0')
            ->orderBy('o.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findLastObsWithImages(int $limit): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.picture IS NOT NULL')
            ->andWhere('o.is_missing = 0')
            ->orderBy('o.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findObsCountThisYear(): int
    {
        $nowYear = date('Y');
        $allObsThisYear = $this->createQueryBuilder('o')
            ->where('YEAR(o.date) = :nowYear')
            ->setParameter('nowYear', $nowYear)
            ->getQuery()
            ->getResult()
        ;

        return count($allObsThisYear);
    }

    // /**
    //  * @return Observation[] Returns an array of Observation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Observation
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
