<?php

namespace App\Twig;

use App\Entity\EventSpecies;
use App\Entity\Individual;
use App\Entity\Observation;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class AppExtension.
 */
class StationExtension extends AbstractExtension
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('setStationListCards', [
                $this,
                'setStationListCards',
            ]),
            new TwigFunction('setStationActionBarSquaredButton', [
                $this,
                'setStationActionBarSquaredButton',
            ]),
            new TwigFunction('setStationCards', [
                $this,
                'setStationCards',
            ]),
        ];
    }

    public function setStationCards(array $stations): array
    {
        $observationsRepository = $this->em->getRepository(Observation::class);
        $individualsRepository = $this->em->getRepository(Individual::class);

        $cards = [];
        foreach ($stations as $station) {
            $individuals = $individualsRepository->findAllIndividualsInStation($station);
            $observations = $observationsRepository->findAllObservationsInStation($station, $individuals);
            $contributorsCount = $observationsRepository->findAllObsContributorsCountInStation($individuals);

            $cards[] = [
                'station' => $station,
                'observations' => $observations,
                'contributorsCount' => $contributorsCount,
            ];
        }

        return $cards;
    }

    public function setStationListCards(
        array $stationSpecies,
        array $stationIndividuals,
        array $stationObservations
    ): array {
        $listCards = [];
        foreach ($stationSpecies as $species) {
            // sets individuals
            $individuals = [];
            foreach ($stationIndividuals as $individual) {
                if ($individual->getSpecies() === $species) {
                    $individuals[] = $individual;
                }
            }
            if (!empty($individuals)) {
                // sets eventsSpecies
                $eventsSpeciesForSingleSpecies = $this->em->getRepository(EventSpecies::class)
                    ->findBy(['species' => $species]);

                // sets observations
                $validEvents = [];
                foreach ($eventsSpeciesForSingleSpecies as $eventSpecies) {
                    $validEvents[] = $eventSpecies->getEvent();
                }
                $observations = [];
                foreach ($stationObservations as $observation) {
                    if (
                        $observation->getIndividual()->getSpecies() === $species
                        && in_array($observation->getEvent(), $validEvents)
                    ) {
                        $observations[] = $observation;
                    }
                }

                // sets years of observations
                $allObsYears = [];
                foreach ($observations as $obs) {
                    $year = date_format($obs->getDate(), 'Y');
                    if (!in_array($year, $allObsYears)) {
                        $allObsYears[] = $year;
                    }
                }

                $listCard = [
                    'individuals' => $individuals,
                    'eventsSpecies' => $eventsSpeciesForSingleSpecies,
                    'observations' => $observations,
                    'allObsYears' => $allObsYears,
                ];
            }
            $listCard['species'] = $species;

            $listCards[] = $listCard;
        }

        return $listCards;
    }
}
