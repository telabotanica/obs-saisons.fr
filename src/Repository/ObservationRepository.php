<?php

namespace App\Repository;

use App\Entity\EventSpecies;
use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Station;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;

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
            ->addOrderBy('o.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllObsContributorsCountInStation(array $stationIndividuals): int
    {
        $contributorsCount = count(
            $this->createQueryBuilder('o')
                ->where('o.individual IN (:individuals)')
                ->setParameter('individuals', $stationIndividuals)
                ->groupBy('o.user')
                ->getQuery()
                ->getResult()
        );

        // if the station exists there is at least one contributor
        if (0 >= $contributorsCount) {
            return 1;
        }

        return $contributorsCount;
    }

    public function findLastObs(int $limit): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.isMissing = 0')
            ->orderBy('o.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findLastUserObs(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->andWhere('o.isMissing = 0')
            ->setParameter('user', $user)
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
            ->andWhere('o.isMissing = 0')
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

    public function findValidObsForIndividual(Individual $individual)
    {
        return $this->createQueryBuilder('o')
            ->leftJoin(Individual::class, 'i', Expr\Join::WITH, 'o.individual = i.id')
            ->innerJoin(EventSpecies::class, 'es', Expr\Join::WITH, 'i.species = es.species')
            ->addSelect('o')
            ->andwhere('o.individual = :individual')
            ->andWhere('o.event = es.event')
            ->setParameter('individual', $individual)
            ->orderBy('o.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
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
