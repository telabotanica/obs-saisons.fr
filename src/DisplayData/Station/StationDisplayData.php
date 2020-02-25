<?php

namespace App\DisplayData\Station;

use App\Entity\Species;
use App\Entity\Individual;
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
        $this->stationAllSpeciesIndividuals = $manager->getRepository(Individual::class)
            ->findSpeciesIndividualsForStation($station)
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
            $species = $individual->getSpecies();
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

    public function filterStationIndividualDataBySpecies(Species $species): array
    {
        $stationIndividualsDataBySpecies = [];
        foreach ($this->stationAllSpeciesIndividuals as $individual) {
            $thisSpecies = $individual->getSpecies();
            if ($thisSpecies === $species) {
                $stationIndividualsDataBySpecies[] = $individual;
            }
        }

        return $stationIndividualsDataBySpecies;
    }

    public function filterStationObservationsBySpecies(Species $species): array
    {
        $stationObservationsBySpecies = [];
        foreach ($this->stationAllObservations as $observation) {
            $thisSpecies = $observation->getIndividual()->getSpecies();
            if ($thisSpecies === $species) {
                $stationObservationsBySpecies[] = $observation;
            }
        }

        return $stationObservationsBySpecies;
    }

    public function setStationAllSpeciesDisplayData(): self
    {
        foreach ($this->stationAllSpecies as $species) {
            $stationSpeciesDisplayData = new StationSpeciesDisplayData(
                $this->station,
                $species,
                $this->manager,
                $this->filterStationIndividualDataBySpecies($species),
                $this->filterStationObservationsBySpecies($species)
            );
            $this->stationAllSpeciesDisplayData[] = $stationSpeciesDisplayData;
        }

        return $this;
    }

    public function getStationAllSpeciesDisplayData(): array
    {
        return $this->stationAllSpeciesDisplayData;
    }

    private function getStationObsWithPictures(): array
    {
        $stationObs = [];
        foreach ($this->stationAllObservations as $observation) {
            if (!empty($observation->getPicture())) {
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
        foreach ($this->getStationObsWithPictures() as $obs) {
            $this->stationObsImages[] = $obs->getPicture();
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
