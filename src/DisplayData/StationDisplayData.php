<?php

namespace App\DisplayData;

use App\Entity\Espece;
use App\Entity\Individu;
use App\Entity\Observation;
use App\Entity\Station;
use Doctrine\Common\Persistence\ManagerRegistry;

class StationDisplayData
{
    private $manager;
    private $station;
    private $stationAllSpecies;
    private $stationAllSpeciesIndividuals;
    private $stationAllObservations;
    private $stationAllSpeciesDisplayData;
    private $stationObsImages;

    public function __construct(Station $station, ManagerRegistry $manager)
    {
        $this->station = $station;
        $this->manager = $manager;
        $this->stationAllSpeciesIndividuals = $manager->getRepository(Individu::class)
            ->findEspecesIndividusForStation($station)
        ;
        $this->stationAllObservations = $manager->getRepository(Observation::class)
            ->findAllObsInStation($station)
        ;
        $this->stationAllSpecies = [];
        $this->stationAllSpeciesDisplayData = [];
        $this->stationObsImages = [];

        self::setStationAllSpecies();
        self::setStationObsImages();
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    private function setStationAllSpecies(): self
    {
        foreach ($this->stationAllSpeciesIndividuals as $individual) {
            $species = $individual->getEspece();
            if (!in_array($species, $this->stationAllSpecies)) {
                $this->stationAllSpecies[] = $species;
            }
        }

        return $this;
    }

    public function getStationAllSpecies(): array
    {
        return $this->stationAllSpecies;
    }

    public function filterStationIndividualDataBySpecies(Espece $species): array
    {
        $stationIndividualsDataBySpecies = [];
        foreach ($this->stationAllSpeciesIndividuals as $individual) {
            $thisSpecies = $individual->getEspece();
            if ($thisSpecies === $species) {
                $stationIndividualsDataBySpecies[] = $individual;
            }
        }

        return $stationIndividualsDataBySpecies;
    }

    public function filterStationObservationsBySpecies(Espece $species): array
    {
        $stationObservationsBySpecies = [];
        foreach ($this->stationAllObservations as $observation) {
            $thisSpecies = $observation->getIndividu()->getEspece();
            if ($thisSpecies === $species) {
                $stationObservationsBySpecies[] = $observation;
            }
        }

        return $stationObservationsBySpecies;
    }

    public function setStationAllSpeciesDisplayData(): self
    {
        foreach ($this->stationAllSpecies as $species) {
            $speciesDisplayData = new SpeciesDisplayData(
                $this->station,
                $species,
                $this->manager,
                $this->filterStationIndividualDataBySpecies($species),
                $this->filterStationObservationsBySpecies($species)
            );
            $this->stationAllSpeciesDisplayData[] = $speciesDisplayData;
        }

        return $this;
    }

    public function getStationAllSpeciesDisplayData(): array
    {
        return $this->stationAllSpeciesDisplayData;
    }

    private function getStationObsWithPhotos(): array
    {
        $stationObs = [];
        foreach ($this->stationAllObservations as $observation) {
            if (!empty($observation->getPhoto())) {
                $stationObs[] = $observation;
            }
        }
        uasort($stationObs, 'self::sortObsByDAte');

        return $stationObs;
    }

    private function sortObsByDAte(Observation $obsA, Observation $obsB)
    {
        return $obsB->getDateObs() <=> $obsA->getDateObs();
    }

    private function setStationObsImages(): self
    {
        foreach ($this->getStationObsWithPhotos() as $obs) {
            $this->stationObsImages[] = $obs->getPhoto();
        }

        return $this;
    }

    public function getStationObsImages(): array
    {
        return $this->stationObsImages;
    }

    public function getCountStationContributors(): int
    {
        $contributors = [$this->station->getUser()];
        foreach ($this->stationAllObservations as $observation) {
            $obsContributor = $observation->getUser();
            if (!in_array($obsContributor, $contributors)) {
                $contributors[] = $obsContributor;
            }
        }
        foreach ($this->stationAllSpeciesIndividuals as $individual) {
            $individualContributor = $individual->getUser();
            if (!in_array($individualContributor, $contributors)) {
                $contributors[] = $individualContributor;
            }
        }

        return count($contributors);
    }
}
