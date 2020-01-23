<?php

namespace App\Repository;

use App\Entity\Individu;
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

    public function findAllObsInStation(Station $station): ArrayCollection
    {
        $individusInStation = $this->getEntityManager()
            ->getRepository(Individu::class)
            ->findBy(['station' => $station]);

        return new ArrayCollection(
            $this->createQueryBuilder('o')
                ->where('o.individu IN (:individus)')
                ->setParameter('individus', $individusInStation)
                ->addOrderBy('o.individu', 'ASC')
                ->addOrderBy('o.obs_date', 'DESC')
                ->getQuery()
                ->getResult()
        );
    }

    public function findLastObsWithImages(int $limit): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.photo IS NOT NULL')
            ->orderBy('o.obs_date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findRandomObs(int $limit): array
    {
        return $this->createQueryBuilder('o')
            ->select('o.photo')
            ->where('o.photo IS NOT NULL')
            ->orderBy('RAND()')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findObsCountThisYear(): int
    {
        $nowYear = date('Y');
        $allObsThisYear = $this->createQueryBuilder('o')
            ->where('YEAR(o.obs_date) = :nowYear')
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
