<?php

namespace App\Repository;

use App\Entity\Individu;
use App\Entity\Observation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Observation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Observation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Observation[]    findAll()
 * @method Observation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ObservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Observation::class);
    }

    public function findAllObsInStation(int $stationId): array
    {
        $individusInStation = $this->getEntityManager()
            ->getRepository(Individu::class)
            ->findIdsForStationId($stationId)
        ;

        return $this->createQueryBuilder('o')
            ->where('o.individu IN (:individusIds)')
            ->setParameter('individusIds', $individusInStation)
            ->addOrderBy('o.individu', 'ASC')
            ->addOrderBy('o.obs_date', 'DESC')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function findLastObsInStation(int $stationId, int $limit = 1): array
    {
        // get all obs in station
        if (0 > $limit) {
            $limit = null;
        }

        return array_slice($this->findAllObsInStation($stationId), 0, $limit);
    }

    // ready to use in station data array observations
    public function generateStationObsDisplayArray(int $stationId): array
    {
        $obsInStation = $this->findAllObsInStation($stationId);

        $obsImages = (array) $this->findObsImages($obsInStation);
        $lastObsImage = null;
        if (!empty($obsImages[0]['image'])) {
            $lastObsImage = $obsImages[0]['image'];
        }

        $obsParEspece = $this->generateStationObs($obsInStation);
        $species = $this->getEntityManager()
            ->getRepository(Individu::class)
            ->generateEspecesIndividusDataArrayForStation($stationId)
        ;
        foreach ($species as $speciesKey => $row) {
            $especeScName = $row['scientific_name'];
            if (isset($obsParEspece[$especeScName])) {
                foreach ($row['individuals'] as $indivKey => $individual) {
                    $indivName = $individual['name'];
                    if (isset($obsParEspece[$especeScName][$indivName])) {
                        foreach ($obsParEspece[$especeScName][$indivName] as $year => $obsByYear) {
                            if (!isset($species[$speciesKey]['individuals'][$indivKey]['observations'][$year])) {
                                $species[$speciesKey]['individuals'][$indivKey]['observations'][$year] = [];
                            }
                            $species[$speciesKey]['individuals'][$indivKey]['observations'][$year] = $obsByYear;
                        }
                    }
                }
            }
        }

        return [
            'last_obs_image' => $lastObsImage,
            'obs_images_count' => count($obsImages),
            'species' => $species,
        ];
    }

    // Observations in station sorted by species individuals and year
    private function generateStationObs(array $stationObsBySpecies): array
    {
        $obsParEspece = [];
        $especeScNames = [];
        $individuNames = [];
        $years = [];
        foreach ($stationObsBySpecies as $obs) {
            $thisObs = $this->find($obs['id']);
            $year = date_format($obs['obs_date'], 'Y');
            $especeScName = $thisObs->getIndividu()->getEspece()->getNomScientifique();
            $individuName = $thisObs->getIndividu()->getNom();
            $stade = ucfirst($thisObs->getEvenement()->getNom()).' - stade '.$thisObs->getEvenement()->getStadeBbch();
            $author = $thisObs->getUser()->getId();

            if (!in_array($especeScName, $especeScNames)) {
                $especeScNames[] = $especeScName;
                $obsParEspece[$especeScName] = [];
            }
            if (!in_array($individuName, $individuNames)) {
                $individuNames[] = $individuName;
                $obsParEspece[$especeScName][$individuName] = [];
            }
            if (!in_array($year, $years)) {
                $years[] = $year;
                $obsParEspece[$especeScName][$individuName][$year] = [];
            }
            $obsParEspece[$especeScName][$individuName][$year][] = [
                'image' => $obs['photo'],
                'stade' => $stade,
                'date' => $obs['obs_date'],
                'author' => $author,
            ];
        }

        return $obsParEspece;
    }

    public function findObsImages(array $obsInStation = null, int $stationId = null): array
    {
        $images = [];
        // using station id
        if (null === $obsInStation) {
            if (null === $stationId) {
                throw new \InvalidArgumentException('Station invalide ou non spécifiée');
            }
            $obsInStation = $this->findAllObsInStation($stationId);
        }
        foreach ($obsInStation as $obs) {
            if (!empty($obs['photo'])) {
                $images[] = [
                    'image' => $obs['photo'],
                    'date' => $obs['obs_date'],
                ];
            }
        }
        usort($images, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return $images;
    }

    public function countObsContributors(array $obsInStation = null, int $stationId = null): int
    {
        $contributors = [];
        // using station id
        if (null === $obsInStation) {
            if (null === $stationId) {
                throw new \InvalidArgumentException('Station invalide ou non spécifiée');
            }
            $obsInStation = $this->findAllObsInStation($stationId);
        }
        foreach ($obsInStation as $obs) {
            $contributor = $this->find($obs['id'])->getUser()->getId();
            if (!empty($contributor) && !in_array($contributor, $contributors)) {
                $contributors[] = $contributor;
            }
        }

        return count($contributors);
    }

    // Obsevations informations for given species in given station
    public function findInfosObsInStationForEspece(int $stationId, int $espece_id): ?array
    {
        $infosObservationsForEspece = ['obs_count' => 0];
        $obsInStation = $this->findAllObsInStation($stationId);
        $years = [];

        $loopObsDate = date('now');
        foreach ($obsInStation as $obs) {
            $thisObs = $this->find($obs['id']);
            $especeId = $thisObs->getIndividu()->getEspece()->getId();
            if ($espece_id === $especeId) {
                $obsDate = $obs['obs_date'];
                $year = date_format($obsDate, 'Y');
                if (!in_array($year, $years)) {
                    $years[] = $year;
                }
                ++$infosObservationsForEspece['obs_count'];
                if ($loopObsDate < $obsDate) {
                    $loopObsDate = $obsDate;
                    $infosObservationsForEspece += [
                        'last_obs_date' => date_format($obsDate, 'j/m/Y'),
                        'last_obs_stade' => ucfirst($thisObs->getEvenement()->getNom()),
                    ];
                }
            }
        }
        $infosObservationsForEspece['years'] = $years;

        return $infosObservationsForEspece;
    }

    // /**
    //  * @return Observation[] Returns an array of Observation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Observation
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
