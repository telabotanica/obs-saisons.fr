<?php

namespace App\Repository;

use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Station;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

    public function countStations(User $user = null)
    {
        $qb = $this->createQueryBuilder('s')
            ->select('count(s.id)');
        if ($user) {
            $qb = $qb->where('s.user = (:user)')
                ->setParameter('user', $user);
        }
        $qb = $qb->getQuery()
            ->getSingleScalarResult()
        ;

        return $qb;
    }

    public function findStationEditArray(Station $station)
    {
        return $this->createQueryBuilder('s')
            ->select(
                's.id,
                s.name,
                s.habitat,
                s.description,
                s.latitude,
                s.longitude,
                s.altitude,
                s.locality,
                s.inseeCode,
                s.isPrivate,
                s.headerImage'
            )
            ->where('s = :station')
            ->setParameter('station', $station)
            ->getQuery()
            ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY)
        ;
    }

    public function findAllOrderedByLastActive(User $user = null)
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin(Individual::class, 'i', Expr\Join::WITH, 's.id = i.station')
            ->leftJoin(Observation::class, 'o', Expr\Join::WITH, 'i.id = o.individual')
            ->addSelect('s')
            ->groupBy('s.id')
        ;
        if ($user) {
            $qb = $qb->where('s.user = (:user)')
            ->setParameter('user', $user);
        }

        return $qb->addOrderBy('MAX(o.createdAt)', 'DESC')
            ->addOrderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Station[]
     */
    public function findAllPaginatedOrderedStations(int $page = 1, int $limit = 11, User $user = null): array
    {
        if (1 > $page) {
            throw new NotFoundHttpException('La page demandée n’existe pas');
        }
        if (1 > $limit) {
            throw new InvalidArgumentException('La valeur de l’argument $limit est incorrecte (valeur : '.$limit.').');
        }
        $firstResult = ($page - 1) * $limit;
        $stations = $this->findAllOrderedByLastActive($user);
        if (!isset($stations[$firstResult]) && 1 != $page) {
            throw new NotFoundHttpException('La page demandée n’existe pas.'); // page 404, sauf pour la première page
        }

        return array_slice($stations, $firstResult, $limit);
    }

    public function search(string $searchTerm, string $searchKey): array
    {
        $qb = $this->createQueryBuilder('s');

        $alias = 's';
        if ('displayName' === $searchKey) {
            $qb->leftJoin(User::class, 'u', Expr\Join::WITH, 's.user = u.id');
            $alias = 'u';
        }
        if (
            'department' === $searchKey &&
            is_numeric(substr($searchTerm, 0, 1))
        ) {
            $searchTerm = substr($searchTerm, 0, 2);
        }

        $searchResults = $qb->andWhere('lower('.$alias.'.'.$searchKey.') =:searchKey')
            ->setParameter('searchKey', strtolower($searchTerm))
            ->getQuery()
            ->getResult()
        ;

        if ('department' !== $searchKey && 3 <= strlen($searchTerm)) {
            $likeSearchResults = $this->likeSearch($searchTerm, $searchKey);
            $regexpSearchResults = $this->regexpSearch($searchTerm, $searchKey);
            $otherMatchingStations = array_merge($likeSearchResults, $regexpSearchResults);

            foreach ($otherMatchingStations as $otherMatchingStation) {
                if (!in_array($otherMatchingStation, $searchResults)) {
                    $searchResults[] = $otherMatchingStation;
                }
            }
        }

        return $searchResults;
    }

    private function likeSearch(string $searchTerm, string $searchKey)
    {
        $qb = $this->createQueryBuilder('s');

        $alias = 's';
        if ('displayName' === $searchKey) {
            $qb->leftJoin(User::class, 'u', Expr\Join::WITH, 's.user = u.id');
            $alias = 'u';
        }

        return $qb->andWhere($qb->expr()->like('lower('.$alias.'.'.$searchKey.')', ':searchKey'))
            ->setParameter('searchKey', strtolower($searchTerm).'%')
            ->getQuery()
            ->getResult()
        ;
    }

    private function regexpSearch(string $searchTerm, string $searchKey)
    {
        $qb = $this->createQueryBuilder('s');

        $alias = 's';
        if ('displayName' === $searchKey) {
            $qb->leftJoin(User::class, 'u', Expr\Join::WITH, 's.user = u.id');
            $alias = 'u';
        }

        $regexp = '.*'.str_replace(' ', '.*', strtolower($searchTerm)).'.*';

        return $qb->andWhere('REGEXP(lower('.$alias.'.'.$searchKey.'), :regexp) = true')
            ->setParameter('regexp', $regexp)
            ->getQuery()
            ->getResult()
        ;
    }

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
