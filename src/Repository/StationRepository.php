<?php

namespace App\Repository;

use App\Entity\Individu;
use App\Entity\Observation;
use App\Entity\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Station|null find($id, $lockMode = null, $lockVersion = null)
 * @method Station|null findOneBy(array $criteria, array $orderBy = null)
 * @method Station[]    findAll()
 * @method Station[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Station::class);
    }

   /* public function countContributors(Station $station)
    {
        $manager = $this->getEntityManager();
        $stationContributors = $manager->getRepository(Observation::class)
            ->listObsContributors($station)
        ;
        $stationAuthors = $manager->getRepository(Individu::class)
            ->listIndividusAuthors($station)
        ;

        $user = $station->getUser();
        if (!empty($user) && !$stationAuthors->contains($user)) {
            $stationAuthors->add($user);
        }

        foreach ($stationAuthors->toArray() as $author) {
            if (!$stationContributors->contains($author)) {
                $stationContributors->add($author);
            }
        }

        return $stationContributors->count();
    }*/

    // /**
    //  * @return Station[] Returns an array of Station objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Station
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
