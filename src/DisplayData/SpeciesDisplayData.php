<?php

namespace App\DisplayData;

use App\Entity\Espece;
use App\Entity\Evenement;
use App\Entity\EvenementEspece;
use App\Entity\Individu;
use App\Entity\Observation;
use App\Entity\Station;
use Doctrine\Common\Persistence\ManagerRegistry;

class SpeciesDisplayData
{
    private $manager;
    private $station;
    private $species;
    private $thisStationIndividuals;
    private $stationSpeciesObservations;
    private $eventsSpecies;
    private $periods;
    private $allObsYears;

    /**
     * @var Observation
     */
    private $lastObservation;
    private $allIndividualsObservationsData;

    public function __construct(
        Station $station,
        Espece $species,
        ManagerRegistry $manager,
        array $thisStationIndividuals = null,
        array $stationSpeciesObservations = null
    ) {
        $this->manager = $manager;
        $this->station = $station;
        $this->species = $species;

        if (null === $thisStationIndividuals) {
            $this->thisStationIndividuals = [];
            self::setStationSpeciesData();
        } else {
            $this->thisStationIndividuals = $thisStationIndividuals;
        }

        if (null === $stationSpeciesObservations) {
            $this->stationSpeciesObservations = [];
            self::setStationObservations();
        } else {
            $this->stationSpeciesObservations = $stationSpeciesObservations;
        }

        $this->eventsSpecies = $manager->getRepository(EvenementEspece::class)
            ->findBy(['espece' => $species], ['espece' => 'asc'])
        ;

        $this->periods = [];
        $this->allIndividualsObservationsData = [];

        self::setAllObsYears();
        self::setPeriods();
        self::setAllIndividualsObservationsDisplayData();
        self::setLastObservation();
    }

    private function setStationSpeciesData(): self
    {
        $allStationIndividualsData = $this->manager->getRepository(Individu::class)
            ->findEspecesIndividusForStation($this->station)
        ;
        foreach ($allStationIndividualsData as $stationIndividuals) {
            $species = $stationIndividuals->getEspece();
            if ($species === $this->species) {
                $this->thisStationIndividuals[] = $stationIndividuals;
            }
        }

        return $this;
    }

    public function getStationSpeciesData(): array
    {
        return $this->thisStationIndividuals;
    }

    private function setStationObservations(): self
    {
        $stationObservations = $this->manager->getRepository(Observation::class)
            ->findAllObsInStation($this->station)
        ;
        foreach ($stationObservations as $stationObservation) {
            $species = $stationObservation->getEspece();
            if ($species === $this->species) {
                $this->stationSpeciesObservations[] = $stationObservation;
            }
        }
        uasort($this->stationSpeciesObservations, 'self::sortObsByDAte');

        return $this;
    }

    public function getSpecies(): Espece
    {
        return $this->species;
    }

    private function setPeriods(): self
    {
        $loopStadeBbch = [];
        foreach ($this->eventsSpecies as $eventSpecies) {
            $event = $eventSpecies->getEvenement();
            $eventName = Evenement::DISPLAY_LABELS[$event->getNom()];
            $StadeBbch = $event->getStadeBbch();

            if (!in_array($eventName, array_keys($this->periods))) {
                $loopStadeBbch[] = $StadeBbch;
                $this->periods[$eventName] = [
                    'begin' => $eventSpecies->getDateDebut(),
                    'end' => $eventSpecies->getDateFin(),
                ];
            }
            if (!in_array($StadeBbch, $loopStadeBbch)) {
                $loopStadeBbch[] = $StadeBbch;
                // the earliest begining date
                if ($eventSpecies->getDateDebut() < $this->periods[$eventName]['begin']) {
                    $this->periods[$eventName]['begin'] = $eventSpecies->getDateDebut();
                }
                // the latest ending date
                if ($eventSpecies->getDateFin() > $this->periods[$eventName]['end']) {
                    $this->periods[$eventName]['end'] = $eventSpecies->getDateFin();
                }
            }
        }

        return $this;
    }

    public function getPeriods(): array
    {
        return $this->periods;
    }

    private function filterStationSpeciesObservationsByIndividuals(Individu $individual)
    {
        $stationSpeciesObservationsByIndividuals = [];
        foreach ($this->stationSpeciesObservations as $observation) {
            $thisIndividual = $observation->getIndividu();
            if ($thisIndividual === $individual) {
                $stationSpeciesObservationsByIndividuals[] = $observation;
            }
        }

        return $stationSpeciesObservationsByIndividuals;
    }

    private function setAllIndividualsObservationsDisplayData(): self
    {
        foreach ($this->thisStationIndividuals as $individual) {
            $individualsDisplayData = new IndividualsDisplayData(
                $individual,
                $this->manager,
                $this->filterStationSpeciesObservationsByIndividuals($individual)
            );
            $this->allIndividualsObservationsData[] = $individualsDisplayData;
        }

        return $this;
    }

    public function getAllIndividualsObservationsDisplayData(): array
    {
        return $this->allIndividualsObservationsData;
    }

    public function getIndividualsCount(): int
    {
        return count($this->thisStationIndividuals);
    }

    public function getObsCount(): int
    {
        return count($this->stationSpeciesObservations);
    }

    private function setAllObsYears(): self
    {
        $this->allObsYears = [];
        foreach ($this->stationSpeciesObservations as $obs) {
            $year = date_format($obs->getDateObs(), 'Y');
            if (!in_array($year, $this->allObsYears)) {
                $this->allObsYears[] = $year;
            }
        }

        return $this;
    }

    public function getAllObsYears(): array
    {
        return $this->allObsYears;
    }

    private function setLastObservation(): self
    {
        $this->lastObservation = reset($this->stationSpeciesObservations);

        return $this;
    }

    public function getLastObsDate(): \DateTimeInterface
    {
        return $this->lastObservation->getDateObs();
    }

    public function getLastObsStade(): string
    {
        return Evenement::DISPLAY_LABELS[$this->lastObservation->getEvenement()->getNom()];
    }

    private function sortObsByDAte(Observation $obsA, Observation $obsB)
    {
        return $obsB->getDateObs() <=> $obsA->getDateObs();
    }
}
