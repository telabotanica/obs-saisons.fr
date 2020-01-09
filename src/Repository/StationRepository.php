<?php

namespace App\Repository;

use App\Entity\Observation;
use App\Entity\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
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

    /**
     * @return station[]
     */
    public function findSationDataDisplayBySlug(string $slug): array
    {
        $sationData = [];

        $station = $this->findOneBy(['slug' => $slug]);
        // @TODO: redirect to 404 page
        if (null === $station) {
            throw new NotFoundHttpException('La page demandée n\'existe pas');
        }
        $stationId = $station->getId();
        $user = $station->getUser();

        if (!empty($station)) {
            $sationData = [
                'slug' => $slug,
                'isPublic' => $station->getIsPublic(),
                'userId' => $user->getId(),
                'name' => $station->getName(),
                'description' => $station->getDescription(),
                'latitude' => $station->getLatitude(),
                'longitude' => $station->getLongitude(),
                //'creator_avatar' => 'https://assets.website-files.com/5ce249c60b5f0ba8c825fa9f/5ce4536c881a4d14015d740b_Capture%20d%E2%80%99e%CC%81cran%202019-05-21%20a%CC%80%2021.37.04.png',
                'creator_name' => $user->getDisplayName(),
            ];
        }
        $sationData += $this->getEntityManager()
            ->getRepository(Observation::class)
            ->generateStationObsDisplayArray($stationId)
        ;

        return $sationData;
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
