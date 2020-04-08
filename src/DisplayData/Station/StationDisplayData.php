<?php

namespace App\DisplayData\Station;

use App\Entity\EventSpecies;
use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Species;
use App\Entity\Station;
use Doctrine\Common\Persistence\ManagerRegistry;

class StationDisplayData
{
    private $manager;
    public $station;
    public $stationAllSpecies;
    public $stationAllSpeciesIndividuals;
    public $stationAllObservations;
    public $stationAllSpeciesDisplayData;
    public $stationObsImages;

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

    public function getStationAllIndividualsIds()
    {
        $stationAllIndividualsIdsArrays = [];
        foreach ($this->stationAllSpeciesIndividuals as $indiv) {
            $stationAllIndividualsIdsArrays[] = $indiv->getId();
        }

        return implode(',', $stationAllIndividualsIdsArrays);
    }

    public function getStationAllSpeciesDisplayData(): array
    {
        return $this->stationAllSpeciesDisplayData;
    }

    public function getEventsSpeciesForSpecies(Species $species): array
    {
        return $this->manager->getRepository(EventSpecies::class)
            ->findBy(['species' => $species])
        ;
    }

    public function getEventIdsForSpecies(Species $species): array
    {
        $eventsForSpeciesIds = [];
        foreach ($this->getEventsSpeciesForSpecies($species) as $eventSpecies) {
            $eventsForSpeciesIds[] = $eventSpecies->getEvent()->getId();
        }

        return $eventsForSpeciesIds;
    }

    public function getEventsForSpecies(Species $species): array
    {
        $eventsForSpecies = [];
        foreach ($this->getEventsSpeciesForSpecies($species) as $eventSpecies) {
            $eventsForSpecies[] = $eventSpecies->getEvent();
        }

        return $eventsForSpecies;
    }

    public function getStationAllEvents(): array
    {
        $allEvents = [];
        foreach ($this->stationAllSpecies as $species) {
            $eventSpeciesForSpecies = $this->getEventsSpeciesForSpecies($species);
            foreach ($eventSpeciesForSpecies as $eventSpecies) {
                $event = $eventSpecies->getEvent();
                if (!in_array($event, $allEvents)) {
                    $allEvents[] = $event;
                }
            }
        }

        return $allEvents;
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
        return $obsB->getObsDate() <=> $obsA->getObsDate();
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
