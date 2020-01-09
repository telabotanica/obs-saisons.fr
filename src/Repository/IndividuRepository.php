<?php

namespace App\Repository;

use App\Entity\Espece;
use App\Entity\Individu;
use App\Entity\Observation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Individu|null find($id, $lockMode = null, $lockVersion = null)
 * @method Individu|null findOneBy(array $criteria, array $orderBy = null)
 * @method Individu[]    findAll()
 * @method Individu[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IndividuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Individu::class);
    }

    public function findIdsForStationId(int $stationId): array
    {
        $ids = [];
        $individus = $this->findBy(['station' => $stationId]);
        foreach ($individus as $individu) {
            $ids[] = $individu->getId();
        }
        return $ids;
    }

    /**
     * @return Individu[]
     */
    public function findEspecesIndividusForStation(int $stationId)
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.espece', 'espece')
            ->leftJoin('i.station', 'station')
            ->where('station.id = :station_id')
            ->setParameter('station_id', $stationId)
            ->orderBy('i.espece', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function generateEspecesIndividusDataArrayForStation(int $stationId): array
    {
        $stationDataDisplay = [];
        $manager = $this->getEntityManager();
        $especeRepository = $manager->getRepository(Espece::class);
        $observationRepository = $manager->getRepository(Observation::class);

        $especesIndividusForStation = $this->findEspecesIndividusForStation($stationId);
        $especeIds = [];
        foreach ($especesIndividusForStation as $especeIndividuForStation) {
            $especeId = $especeIndividuForStation->getEspece()->getId();
            if (!in_array($especeId, $especeIds)) {
                $especeIds[] = $especeId;
                $especeDataDisplay = $especeRepository->findDataToDisplayInStation($especeId);
                $especeDataDisplay += $observationRepository->findInfosObsInStationForEspece($stationId, $especeId);
                array_push($stationDataDisplay, $especeDataDisplay);
            }
            $especeKey = array_search($especeId, $especeIds);
            array_push($stationDataDisplay[$especeKey]['individuals'], [
                'name' => $especeIndividuForStation->getNom(),
                'observations' => [],
            ]);
        }
        return $stationDataDisplay;
    }

    // /**
    //  * @return Individu[] Returns an array of Individu objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Individu
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
