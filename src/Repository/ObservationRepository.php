<?php

namespace App\Repository;

use App\Entity\FrenchRegions;
use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Station;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    public function findAllPublic(): array
    {
        $qb = $this->createQueryBuilder('o');

        return $qb
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
            ->andWhere($qb->expr()->eq('s.isPrivate', $qb->expr()->literal(false)))
            ->addSelect(['s', 'i'])
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function findMinYear(): string
    {
        $minYear = '2006';

        $data = $this->createQueryBuilder('o')
            ->select('o.date as min')
            ->orderBy('o.date', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
        ;

        if ($data) {
            /** @var \DateTime $date */
            $date = $data[0]['min'];
            $minYear = $date->format('Y');
        }

        return $minYear;
    }

    public function findByStationSlugForExport(string $stationSlug): array
    {
        $qb = $this->createQueryBuilder('o');
        return $qb
            ->innerJoin('o.individual', 'i')
            ->innerJoin('o.event', 'e')
            ->innerJoin('i.station', 'st')
            ->innerJoin('i.species', 'sp')
            ->innerJoin('sp.type', 'ts')
            ->addSelect(['i', 'e', 'st', 'sp', 'ts'])
            ->andWhere($qb->expr()->eq('st.slug', ':slug'))
            ->setParameter(':slug', $stationSlug)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findWithFilters(
        ?string $year,
        ?string $typeSpecies,
        ?string $species,
        ?string $event,
        ?string $department,
        ?string $region,
        ?string $station,
        ?string $individual
    ): array {
        $qb = $this->createQueryBuilder('o')
            ->innerJoin('o.individual', 'i')
            ->innerJoin('o.event', 'e')
            ->innerJoin('i.station', 'st')
            ->innerJoin('i.species', 'sp')
            ->innerJoin('sp.type', 'ts')
            ->addSelect(['i', 'e', 'st', 'sp', 'ts'])
        ;

        if ($year) {
            $qb->andWhere($qb->expr()->eq('YEAR(o.date)', ':year'))
                ->setParameter(':year', $year)
            ;
        }

        if ($typeSpecies) {
            $qb->andWhere($qb->expr()->eq('ts.id', ':typeSpecies'))
                ->setParameter(':typeSpecies', $typeSpecies)
            ;
        }

        if ($species) {
            $qb->andWhere($qb->expr()->eq('sp.id', ':species'))
                ->setParameter(':species', $species)
            ;
        }

        if ($event) {
            $events = explode(',', $event);
            $qb->andWhere($qb->expr()->in('e.id', ':event'))
                ->setParameter(':event', $events)
            ;
        }

        if ($region) {
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('st.department', ':departments'))
                ->setParameter(':departments', $departments)
            ;
        } elseif ($department) {
            $qb->andWhere($qb->expr()->eq('st.department', ':department'))
                ->setParameter(':department', $department)
            ;
        }

        if ($station) {
            $qb->andWhere($qb->expr()->eq('st.slug', ':station'))
                ->setParameter(':station', $station)
            ;
        }

        if ($individual) {
            $qb->andWhere($qb->expr()->eq('i.slug', ':individual'))
                ->setParameter(':individual', $individual)
            ;
        }

        // remove this line for real export:
        $qb->andWhere($qb->expr()->eq('o.isMissing', 0));

        $qb->orderBy('o.date', 'DESC');

        return $qb
            ->getQuery()
            ->getResult()
        ;
    }

    public function findFilteredForEventsEvolutionChart(
        string $species,
        string $event,
        ?string $region,
        ?string $department
    ) {
        $events = explode(',', $event);
        $qb = $this->createQueryBuilder('o');
        $qb
            ->innerJoin('o.individual', 'i')
            ->innerJoin('o.event', 'e')
            ->innerJoin('i.station', 'st')
            ->innerJoin('i.species', 'sp')
            ->select('YEAR(o.date) as year, AVG(DAYOFYEAR(o.date)) as dayOfYear')
            ->addSelect('MAKEDATE(YEAR(o.date), AVG(DAYOFYEAR(o.date))) as date, COUNT(o.date) as date_count')
            ->addSelect('CONCAT(e.name, \' \', e.bbch_code) as event')
            ->andWhere($qb->expr()->eq('sp.id', ':species'))
            ->setParameter(':species', $species)
            ->andWhere($qb->expr()->in('e.id', ':event'))
            ->setParameter(':event', $events)
            ->andWhere($qb->expr()->eq('o.isMissing', 0))
        ;

        if ($region) {
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('st.department', ':departments'))
                ->setParameter(':departments', $departments)
            ;
        } elseif ($department) {
            $qb->andWhere($qb->expr()->eq('st.department', ':department'))
                ->setParameter(':department', $department)
            ;
        }

        $qb
            ->addGroupBy('year')
            ->addGroupBy('event')
        ;

        return $qb
            ->getQuery()
            ->getScalarResult()
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
