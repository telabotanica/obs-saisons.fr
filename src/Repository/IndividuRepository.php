<?php

namespace App\Repository;

use App\Entity\Espece;
use App\Entity\Individu;
use App\Entity\Observation;
use App\Entity\Station;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    public function findEspecesIndividusForStation(Station $station): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.espece', 'espece')
            ->leftJoin('i.station', 'station')
            ->where('station = :station')
            ->setParameter('station', $station)
            ->orderBy('i.espece', 'ASC')
            ->getQuery()
            ->getResult()
        ;

    }

    /*public function generateEspecesIndividusDataArrayForStation(Station $station): array
    {
        $stationDataDisplay = [];
        $manager = $this->getEntityManager();
        $especeRepository = $manager->getRepository(Espece::class);
        $observationRepository = $manager->getRepository(Observation::class);

        $especesIndividusForStation = $this->findEspecesIndividusForStation($station);
        $especes = [];
        foreach ($especesIndividusForStation as $especeIndividuForStation) {
            $espece = $especeIndividuForStation->getEspece();
            if (!in_array($espece, $especes)) {
                $especes[] = $espece;
                $especeDataDisplay = $especeRepository->findEspeceDataToDisplayInStation($espece);
                $especeDataDisplay += $observationRepository->findInfosObsInStationForEspece($station, $espece);
                $stationDataDisplay[] = $especeDataDisplay;
            }
            $especeKey = array_search($espece, $especes);
            $stationDataDisplay[$especeKey]['individuals'][] = [
                'name' => $especeIndividuForStation->getNom(),
                'observations' => [],
            ];
        }

        return $stationDataDisplay;
    }

    public function listIndividusAuthors(Station $station): ArrayCollection
    {
        $authors = new ArrayCollection();
        if (null === $station) {
            throw new \InvalidArgumentException('Station invalide ou non spécifiée');
        }
        $individus = $this->findBy(['station' => $station]);
        foreach ($individus as $individu) {
            $author = $individu->getUser();
            if (!empty($author) && !$authors->contains($author)) {
                $authors->add($author);
            }
        }

        return $authors;
    }*/

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
