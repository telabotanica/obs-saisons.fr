<?php

namespace App\Repository;

use App\Entity\Species;
use App\Entity\TypeSpecies;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Species|null find($id, $lockMode = null, $lockVersion = null)
 * @method Species|null findOneBy(array $criteria, array $orderBy = null)
 * @method Species[]    findAll()
 * @method Species[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpeciesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Species::class);
    }

    public function findAllOrderedByScientificName()
    {
        $qb = $this->createQueryBuilder('s')
            ->orderBy('s.scientific_name', 'ASC');

        $query = $qb->getQuery();

        return $query->execute();
    }

    public function findAllOrderedByTypeAndVernacularName()
    {
        $qb = $this->createQueryBuilder('s')
            ->addOrderBy('s.type', 'ASC')
            ->addOrderBy('s.vernacular_name', 'ASC');

        $query = $qb->getQuery();

        return $query->execute();
    }

    public function findAllByTypeSpecies(TypeSpecies $typeSpecies)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.type = :val')
            ->setParameter('val', $typeSpecies)
            ->getQuery()
            ->getResult()
            ;
    }

    public function findAllActiveArray(): array
    {
        $qb = $this->createQueryBuilder('s');

        return $qb
            ->andWhere($qb->expr()->eq('s.is_active', $qb->expr()->literal(true)))
            ->addOrderBy('s.vernacular_name', 'ASC')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function findAllActive()
    {
        $qb = $this->createQueryBuilder('s');

        return $qb
            ->andWhere($qb->expr()->eq('s.is_active', $qb->expr()->literal(true)))
            ->addOrderBy('s.vernacular_name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return Species[] Returns an array of Species objects
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
    public function findOneBySomeField($value): ?Species
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
