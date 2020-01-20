<?php

namespace App\DisplayData;

use App\Entity\Individu;
use App\Entity\Observation;
use Doctrine\Common\Persistence\ManagerRegistry;

class IndividualsDisplayData
{
    private $manager;
    private $individual;
    private $individualObservations;
    private $allObsYears;
    private $observationsByYearDisplayData;

    public function __construct(Individu $individual, ManagerRegistry $manager, array $individualObservations = null)
    {
        $this->manager = $manager;
        $this->individual = $individual;

        if (null === $individualObservations) {
            $this->individualObservations = $this->manager->getRepository(Observation::class)
                ->findBy(['individu' => $this->individual], ['obs_date' => 'DESC'])
            ;
        } else {
            $this->individualObservations = $individualObservations;
        }

        $this->allObsYears = [];
        $this->observationsByYearDisplayData = [];

        self::setAllObsYears();
        self::setObservationsByYearDisplayData();
    }

    public function getIndividualObservations(): array
    {
        return $this->individualObservations;
    }

    public function getIndividual(): Individu
    {
        return $this->individual;
    }

    private function setAllObsYears(): self
    {
        foreach ($this->individualObservations as $obs) {
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

    private function filterObservationsByYear(string $year)
    {
        $yearObservations = [];
        foreach ($this->individualObservations as $obs) {
            $obsYear = date_format($obs->getDateObs(), 'Y');
            if ($year === $obsYear) {
                $yearObservations[] = $obs;
            }
        }

        return $yearObservations;
    }

    private function setObservationsByYearDisplayData(): self
    {
        foreach ($this->allObsYears as $year) {
            $yearObservations = $this->filterObservationsByYear($year);
            $observationsByYearDisplayData = new ObservationsByYearDisplayData($this->individual, $year, $this->manager, $yearObservations);
            $this->observationsByYearDisplayData[] = $observationsByYearDisplayData;
        }

        return $this;
    }

    public function getObservationsByYearDisplayData(): array
    {
        return $this->observationsByYearDisplayData;
    }
}
