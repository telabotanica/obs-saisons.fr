<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventSpecies;
use App\Entity\Species;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;

/**
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function findAllWithBbch()
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.stade_bbch IS NOT NULL')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findAllWithoutBbch()
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.stade_bbch IS NULL')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findAllArray()
    {
        return $this->createQueryBuilder('e')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function findBySpeciesArray(Species $species)
    {
        return $this->createQueryBuilder('e')
            ->innerJoin(EventSpecies::class, 'es', Expr\Join::WITH, 'e.id = es.event')
            ->andWhere('es.species = :species')
            ->setParameter(':species', $species)
            ->getQuery()
            ->getArrayResult()
        ;
    }

    // /**
    //  * @return Event[] Returns an array of Event objects
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
    public function findOneBySomeField($value): ?Event
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
