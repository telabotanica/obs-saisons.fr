<?php

namespace App\DisplayData;

use App\Entity\Individu;
use App\Entity\Observation;
use Doctrine\Common\Persistence\ManagerRegistry;

class ObservationsByYearDisplayData
{
    private $manager;
    private $individual;
    private $year;
    private $thisYearObservations;

    public function __construct(Individu $individual, string $year, ManagerRegistry $manager, array $thisYearObservations = null)
    {
        $this->manager = $manager;
        $this->individual = $individual;
        $this->year = $year;
        if (null === $thisYearObservations) {
            self::setThisYearObservations();
        } else {
            $this->thisYearObservations = $thisYearObservations;
        }
    }

    private function setThisYearObservations(): self
    {
        $this->thisYearObservations = [];
        $allObservations = $this->manager->getRepository(Observation::class)
            ->findBy(['individu' => $this->individual], ['obs_date' => 'DESC'])
        ;
        foreach ($allObservations as $obs) {
            $obsYear = date_format($obs->getDateObs(), 'Y');
            if ($obsYear === $this->year) {
                $this->thisYearObservations[] = $obs;
            }
        }

        return $this;
    }

    public function getThisYearObservations(): array
    {
        return $this->thisYearObservations;
    }


    public function getYear(): string
    {
        return $this->year;
    }

    public function getIndividual(): Individu
    {
        return $this->individual;
    }
}
