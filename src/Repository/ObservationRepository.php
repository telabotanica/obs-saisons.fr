<?php

namespace App\Repository;

use App\Entity\Espece;
use App\Entity\Evenement;
use App\Entity\Individu;
use App\Entity\Observation;
use App\Entity\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    public function findAllObsInStation(Station $station): ArrayCollection
    {
        $individusInStation = $this->getEntityManager()
            ->getRepository(Individu::class)
            ->findBy(['station' => $station]);

        return new ArrayCollection(
            $this->createQueryBuilder('o')
                ->where('o.individu IN (:individus)')
                ->setParameter('individus', $individusInStation)
                ->addOrderBy('o.individu', 'ASC')
                ->addOrderBy('o.obs_date', 'DESC')
                ->getQuery()
                ->getResult()
        );
    }

    /*public function findLastObsInStation(Station $station, int $limit = 1): array
    {
        // get all obs in station
        if (0 > $limit) {
            $limit = null;
        }

        return array_slice($this->findAllObsInStation($station), 0, $limit);
    }

    // ready to use in station data array observations
    public function generateStationObsDisplayArray(Station $station): array
    {
        $obsParEspece = $this->generateStationObs($station);
        $obsDataBySpecies = $this->getEntityManager()
            ->getRepository(Individu::class)
            ->generateEspecesIndividusDataArrayForStation($station)
        ;
        foreach ($obsDataBySpecies as $speciesKey => $row) {
            $especeScName = $row['espece']->getNomScientifique();
            if (isset($obsParEspece[$especeScName])) {
                foreach ($row['individuals'] as $indivKey => $individual) {
                    $indivName = $individual['name'];
                    if (isset($obsParEspece[$especeScName][$indivName])) {
                        foreach ($obsParEspece[$especeScName][$indivName] as $year => $obsByYear) {
                            if (!isset($obsDataBySpecies[$speciesKey]['individuals'][$indivKey]['observations'][$year])) {
                                $obsDataBySpecies[$speciesKey]['individuals'][$indivKey]['observations'][$year] = [];
                            }
                            $obsDataBySpecies[$speciesKey]['individuals'][$indivKey]['observations'][$year] = $obsByYear;
                        }
                    }
                }
            }
        }

        return $obsDataBySpecies;
    }

    // Observations in station sorted by species individuals and year
    private function generateStationObs(Station $station): array
    {
        $obsParEspece = [];
        $obsInStation = $this->findAllObsInStation($station);
        $especeScNames = [];
        $individuNames = [];
        $years = [];
        foreach ($obsInStation as $obs) {
            $year = date_format($obs->getDateObs(), 'Y');
            $especeScName = $obs->getIndividu()->getEspece()->getNomScientifique();
            $individuName = $obs->getIndividu()->getNom();
            $stade = Evenement::DISPLAY_LABELS[$obs->getEvenement()->getNom()].' - stade '.$obs->getEvenement()->getStadeBbch();

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
                'image' => $obs->getPhoto(),
                'stade' => $stade,
                'date' => $obs->getDateObs(),
                'author' => $obs->getUser(),
            ];
        }

        return $obsParEspece;
    }

    public function findObsImages(Station $station): ArrayCollection
    {
        $images = new ArrayCollection();
        if (null === $station) {
            throw new \InvalidArgumentException('Station invalide ou non spécifiée');
        }
        $obsInStation = $this->findAllObsInStation($station);
        foreach ($obsInStation as $obs) {
            $photo = $obs->getPhoto();
            if (!empty($photo)) {
                $images->add($photo);
            }
        }
        // observations are sorted by obs_date
        return $images;
    }

    public function listObsContributors(Station $station): ArrayCollection
    {
        $contributors = new ArrayCollection();
        if (null === $station) {
            throw new \InvalidArgumentException('Station invalide ou non spécifiée');
        }
        $obsInStation = $this->findAllObsInStation($station);
        foreach ($obsInStation as $obs) {
            $contributor = $obs->getUser();
            if (!empty($contributor) && !$contributors->contains($contributor)) {
                $contributors->add($contributor);
            }
        }

        return $contributors;
    }

    // Obsevations informations for given species in given station
    public function findInfosObsInStationForEspece(Station $station, Espece $espece): ?array
    {
        $infosObservationsForEspece = ['obs_count' => 0];
        $obsInStation = $this->findAllObsInStation($station);
        $years = [];

        $loopObsDate = date('now');
        foreach ($obsInStation as $obs) {
            $thisObsEspece = $obs->getIndividu()->getEspece();
            if ($espece === $thisObsEspece) {
                $obsDate = $obs->getDateObs();
                $year = date_format($obsDate, 'Y');
                if (!in_array($year, $years)) {
                    $years[] = $year;
                }
                ++$infosObservationsForEspece['obs_count'];
                if ($loopObsDate < $obsDate) {
                    $loopObsDate = $obsDate;
                    $infosObservationsForEspece += [
                        'last_obs_date' => $obsDate,
                        'last_obs_stade' => Evenement::DISPLAY_LABELS[$obs->getEvenement()->getNom()],
                    ];
                }
            }
        }
        $infosObservationsForEspece['years'] = $years;

        return $infosObservationsForEspece;
    }*/

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
