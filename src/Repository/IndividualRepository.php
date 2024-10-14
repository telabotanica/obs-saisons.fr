<?php

namespace App\Repository;

use App\Entity\Individual;
use App\Entity\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    public function findAllIndividualsInStation(Station $station)
    {
        return $this->createQueryBuilder('i')
            ->where('i.station = (:station)')
            ->setParameter('station', $station)
            ->orderBy('i.species', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    
}
