<?php

namespace App\DisplayData\Station;

use App\Entity\EventSpecies;
use App\Entity\Individual;
use App\Entity\Observation;
use Doctrine\Common\Persistence\ManagerRegistry;

class StationIndividualsDisplayData
{
    private $manager;
    private $individual;
    private $validEvents;
    private $individualObservations;
    private $allObsYears;
    private $stationObservationsByYearDisplayData;

    public function __construct(Individual $individual, ManagerRegistry $manager, array $individualObservations = null)
    {
        $this->manager = $manager;
        $this->individual = $individual;

        if (null === $individualObservations) {
            $this->individualObservations = $this->manager->getRepository(Observation::class)
                ->findBy(['individual' => $this->individual], ['date' => 'DESC'])
            ;
        } else {
            $this->individualObservations = $individualObservations;
        }

        $this->validEvents = [];
        $this->allObsYears = [];
        $this->stationObservationsByYearDisplayData = [];

        self::setValidEvents();
        self::setAllObsYears();
        self::setStationObservationsByYearDisplayData();
    }

    public function getIndividual(): Individual
    {
        return $this->individual;
    }

    public function setValidEvents(): self
    {
        $eventsForSpecies = $this->manager->getRepository(EventSpecies::class)->findBy(['species' => $this->individual->getSpecies()]);
        foreach ($eventsForSpecies as $eventSpecies) {
            $this->validEvents[] = $eventSpecies->getEvent();
        }

        return $this;
    }

    private function setAllObsYears(): self
    {
        foreach ($this->individualObservations as $obs) {
            $year = date_format($obs->getDate(), 'Y');
            if (!in_array($year, $this->allObsYears) && in_array($obs->getEvent(), $this->validEvents)) {
                $this->allObsYears[] = $year;
            }
        }

        return $this;
    }

    public function getAllObsYears(): array
    {
        return $this->allObsYears;
    }

    private function filterObservationsByYear(string $year)
    {
        $yearObservations = [];
        foreach ($this->individualObservations as $obs) {
            $obsYear = date_format($obs->getDate(), 'Y');
            if ($year === $obsYear && in_array($obs->getEvent(), $this->validEvents)) {
                $yearObservations[] = $obs;
            }
        }

        return $yearObservations;
    }

    private function setStationObservationsByYearDisplayData(): self
    {
        foreach ($this->allObsYears as $year) {
            $yearObservations = $this->filterObservationsByYear($year);
            $stationObservationsByYearDisplayData = new StationObservationsByYearDisplayData($this->individual, $year, $this->manager, $yearObservations);
            $this->stationObservationsByYearDisplayData[] = $stationObservationsByYearDisplayData;
        }

        return $this;
    }

    public function getStationObservationsByYearDisplayData(): array
    {
        return $this->stationObservationsByYearDisplayData;
    }
}
