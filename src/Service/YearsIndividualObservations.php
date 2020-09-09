<?php

namespace App\Service;

use App\Entity\Individual;
use App\Entity\Observation;
use Doctrine\ORM\EntityManagerInterface;

class YearsIndividualObservations
{
    private $em;

    private $individual;

    public $validIndividualObservations;

    public function __construct(EntityManagerInterface $em, Individual $individual)
    {
        $this->em = $em;
        $this->individual = $individual;
        $this->validIndividualObservations = [];
        self::setValidIndividualObservations();
    }

    private function setValidIndividualObservations(): self
    {
        $this->validIndividualObservations = $this->em->getRepository(Observation::class)
            ->findValidObsForIndividual($this->individual)
        ;

        return $this;
    }

    /**
     * helps displaying observations into calendar.
     *
     * @return array
     */
    public function getObservationsPerYears()
    {
        $observationsPerYear = [];
        $obsYears = [];

        /**
         * @var Observation $observation
         */
        foreach ($this->validIndividualObservations as $observation) {
            $year = date_format($observation->getDate(), 'Y');
            $i = array_search($year, $obsYears);
            if (false === $i) {
                $obsYears[] = $year;
                $observationsPerYear[] = [
                    'year' => $year,
                    'observations' => [$observation],
                ];
            } else {
                $observationsPerYear[$i]['observations'][] = $observation;
            }
        }

        return $observationsPerYear;
    }

    /**
     * returns an array in which "animaux" (type species reign)
     * or "herbacée" (type species name) species individuals
     * already have observation
     * (isMissing observations excluded from account).
     *
     * @return array
     */
    public function yearsForbidNewObs()
    {
        $yearsForbidNewObs = [];
        $speciesType = $this->individual->getSpecies()->getType();
        if ('animaux' === $speciesType->getReign() || 'herbacées' === $speciesType->getName()) {
            foreach ($this->validIndividualObservations as $observation) {
                $year = date_format($observation->getDate(), 'Y');
                if (!in_array($year, $yearsForbidNewObs) && !$observation->getIsmissing()) {
                    $yearsForbidNewObs[] = $year;
                }
            }
        }

        return $yearsForbidNewObs;
    }
}
