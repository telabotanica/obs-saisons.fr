<?php

namespace App\Repository;

use App\Entity\Species;
use App\Entity\TypeSpecies;
use App\Entity\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Individual;
use App\Entity\Observation;
use Doctrine\ORM\Query\Expr;
use App\Entity\FrenchRegions;

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
            ->getResult();
        
    }

    public function get3mainSpecies($region)
    {
        $date=date("Y/m/d");
        
        $species = $this->createQueryBuilder('s')
                ->select(
                    "CONCAT(s.vernacular_name,' / ',s.scientific_name) AS specie,count(o.id) AS nombre"
                )
                ->innerJoin(Individual::class, 'i', Expr\Join::WITH, 'i.species = s.id')
                ->innerJoin(Observation::class, 'o', Expr\Join::WITH, 'i.id = o.individual')
                ->innerJoin(Station::class, 'st', Expr\Join::WITH, 'st.id = i.station')
                ->where("o.date=$date");
        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $species->andWhere($species->expr()->in('st.department', ':departments'))
                ->setParameter(':departments', $departments);
        }
                
        return $species
            ->groupBy("s.vernacular_name")
            ->orderBy("nombre","DESC")
            ->setMaxResults(3)
            ->getQuery()
            ->execute();
    }

    public function get3mainSpecies2015($region)
    {
        $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
                    
        $species = $this->createQueryBuilder('s')
                ->select(
                    "CONCAT(s.vernacular_name,' / ',s.scientific_name) AS specie,count(o.id) AS nombre"
                )
                ->innerJoin(Individual::class, 'i', Expr\Join::WITH, 'i.species = s.id')
                ->innerJoin(Observation::class, 'o', Expr\Join::WITH, 'i.id = o.individual')
                ->innerJoin(Station::class, 'st', Expr\Join::WITH, 'st.id = i.station')
                ->where("YEAR(o.date)>=2015");
        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $species->andWhere($species->expr()->in('st.department', ':departments'))
                ->setParameter(':departments', $departments);
        }
                
        return $species
            ->groupBy("s.vernacular_name")
            ->orderBy("nombre","DESC")
            ->setMaxResults(3)
            ->getQuery()
            ->execute();
    }

    public function get3mainSpecies2007($region)
    {
      
        $species = $this->createQueryBuilder('s')
                ->select(
                    "CONCAT(s.vernacular_name,' / ',s.scientific_name) AS specie,count(o.id) AS nombre"
                )
                ->innerJoin(Individual::class, 'i', Expr\Join::WITH, 'i.species = s.id')
                ->innerJoin(Observation::class, 'o', Expr\Join::WITH, 'i.id = o.individual')
                ->innerJoin(Station::class, 'st', Expr\Join::WITH, 'st.id = i.station')
                ->where("YEAR(o.date)>=2007");
                
        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $species->andWhere($species->expr()->in('st.department', ':departments'))
                ->setParameter(':departments', $departments);
        }
                
       return $species
            ->groupBy("s.vernacular_name")
            ->orderBy("nombre","DESC")
            ->setMaxResults(3)
            ->getQuery()
            ->execute();
    }

    public function countMonitoredSpecies()
    {
      
        $species = $this->createQueryBuilder('s')
                ->select(
                    "COUNT(DISTINCT(s.id))"
                )
                ->innerJoin(Individual::class, 'i', Expr\Join::WITH, 'i.species = s.id')
                ->innerJoin(Observation::class, 'o', Expr\Join::WITH, 'i.id = o.individual')
                ->getQuery()
                ->getSingleScalarResult();
        
              
       return $species;
    }


}
