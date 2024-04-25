<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventSpecies;
use App\Entity\Species;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;

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
            ->andWhere('e.bbch_code IS NOT NULL')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findAllWithoutBbch()
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.bbch_code IS NULL')
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

    public function findAllObservable(){

        $stadesQuery ='';
        try{
            //Requete afin de récupérer les différente stades à observé pour le filtrage
            $stadesQuery = $this->createQueryBuilder('e')
                ->select('partial e.{id, name}')
                ->where('e.is_observable = :is_observable')
                ->orderBy('e.id', 'ASC')
                ->setParameter('is_observable', 1);
        }catch(\Exception $exception){
            echo 'An error occured -->' . $exception;
        }


        return $stadesQuery->getQuery()->getResult();
    }
}
