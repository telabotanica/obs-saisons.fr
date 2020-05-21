<?php

namespace App\DisplayData\Station;

use App\Entity\Event;
use App\Entity\EventSpecies;
use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Species;
use App\Entity\Station;
use Doctrine\Common\Persistence\ManagerRegistry;

class StationSpeciesDisplayData
{
    private $manager;
    private $station;
    private $validEvents;
    public $stationSpeciesObservations;
    public $species;
    public $stationIndividuals;
    public $eventsSpecies;
    public $allObsYears;

    /**
     * @var Observation
     */
    private $lastObservation;
    public $allIndividualsObservationsData;

    public function __construct(
        Station $station,
        Species $species,
        ManagerRegistry $manager,
        array $stationIndividuals = null,
        array $stationSpeciesObservations = null
    ) {
        $this->manager = $manager;
        $this->station = $station;
        $this->species = $species;

        if (null === $stationIndividuals) {
            $this->stationIndividuals = [];
            self::setStationSpeciesData();
        } else {
            $this->stationIndividuals = $stationIndividuals;
        }

        $this->validEvents = [];
        self::setValidEvents();

        if (null === $stationSpeciesObservations) {
            $this->stationSpeciesObservations = [];
            self::setStationSpeciesObservations();
        } else {
            $this->stationSpeciesObservations = $stationSpeciesObservations;
        }

        $this->eventsSpecies = $manager->getRepository(EventSpecies::class)
            ->findBy(['species' => $species], ['species' => 'asc'])
        ;

        $this->allIndividualsObservationsData = [];

        self::setAllObsYears();
        self::setAllIndividualsObservationsDisplayData();
    }

    private function setStationSpeciesData(): self
    {
        $allStationIndividualsData = $this->manager->getRepository(Individual::class)
            ->findSpeciesIndividualsForStation($this->station)
        ;
        foreach ($allStationIndividualsData as $stationIndividuals) {
            $species = $stationIndividuals->getSpecies();
            if ($species === $this->species) {
                $this->stationIndividuals[] = $stationIndividuals;
            }
        }

        return $this;
    }

    public function setValidEvents(): self
    {
        $eventsForSpecies = $this->manager->getRepository(EventSpecies::class)->findBy(['species' => $this->species]);
        foreach ($eventsForSpecies as $eventSpecies) {
            $this->validEvents[] = $eventSpecies->getEvent();
        }

        return $this;
    }

    private function setStationSpeciesObservations(): self
    {
        $stationObservations = $this->manager->getRepository(Observation::class)
            ->findAllObsInStation($this->station)
        ;

        $stationObservations->filter('self::filterObsForSpeciesAndValidEvents');
        uasort($stationObservations, 'self::sortObsByDAte');
        $this->stationSpeciesObservations = $stationObservations;

        return $this;
    }

    private function filterStationSpeciesObservationsByIndividuals(Individual $individual)
    {
        $stationSpeciesObservationsByIndividuals = [];
        foreach ($this->stationSpeciesObservations as $observation) {
            $thisIndividual = $observation->getIndividual();
            if ($thisIndividual === $individual) {
                $stationSpeciesObservationsByIndividuals[] = $observation;
            }
        }

        return $stationSpeciesObservationsByIndividuals;
    }

    private function setAllIndividualsObservationsDisplayData(): self
    {
        foreach ($this->stationIndividuals as $individual) {
            $stationIndividualsDisplayData = new StationIndividualsDisplayData(
                $individual,
                $this->manager,
                $this->filterStationSpeciesObservationsByIndividuals($individual)
            );
            $this->allIndividualsObservationsData[] = $stationIndividualsDisplayData;
        }

        return $this;
    }

    private function setAllObsYears(): self
    {
        $this->allObsYears = [];
        foreach ($this->stationSpeciesObservations as $obs) {
            $year = date_format($obs->getDate(), 'Y');
            if (!in_array($year, $this->allObsYears)) {
                $this->allObsYears[] = $year;
            }
        }

        return $this;
    }

    private function sortObsByDAte(Observation $obsA, Observation $obsB): int
    {
        return $obsB->getDate() <=> $obsA->getDate();
    }

    private function filterObsForSpeciesAndValidEvents(Observation $observation): bool
    {
        return $observation->getSpecies() === $this->species && in_array($observation->getEvent(), $this->validEvents);
    }
}
