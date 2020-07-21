<?php

// src/Twig/AppExtension.php

namespace App\Twig;

use App\Entity\EventSpecies;
use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Species;
use App\Entity\Station;
use App\Service\EntityJsonSerialize;
use App\Service\HandleDateTime;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class AppExtension.
 */
class AppExtension extends AbstractExtension
{
    private $security;
    private $manager;

    public function __construct(Security $security, EntityManagerInterface $manager)
    {
        $this->security = $security;
        $this->manager = $manager;
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('eventDatesDisplay', [
                $this,
                'displayEventDates',
            ]),
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
            new TwigFunction('setSpeciesDisplayData', [
                $this,
                'setSpeciesDisplayData',
            ]),
            new TwigFunction('setObsChips', [
                $this,
                'setObsChips',
            ]),
            new TwigFunction('getEventsSpeciesForSpecies', [
                $this,
                'getEventsSpeciesForSpecies',
            ]),
        ];
    }

    /**
     * @return array|TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('slugify', [
                $this,
                'slugify',
            ]),
            new TwigFilter('removeDuplicates', [
                $this,
                'removeDuplicates',
            ]),
            new TwigFilter('getJsonSerializedEditStation', [
                $this,
                'getJsonSerializedEditStation',
            ]),
            new TwigFilter('getJsonSerializedEditIndividual', [
                $this,
                'getJsonSerializedEditIndividual',
            ]),
            new TwigFilter('getJsonSerializedEditObservation', [
                $this,
                'getJsonSerializedEditObservation',
            ]),
        ];
    }

    public function slugify(string $string): string
    {
        $slugGenerator = new SlugGenerator();

        return $slugGenerator->slugify($string);
    }

    public function getJsonSerializedEditStation(Station $station)
    {
        $entityJsonSerialize = new EntityJsonSerialize();

        return $entityJsonSerialize->getJsonSerializedEditStation($station);
    }

    public function getJsonSerializedEditIndividual(Individual $individual)
    {
        $entityJsonSerialize = new EntityJsonSerialize();

        return $entityJsonSerialize->getJsonSerializedEditIndividual($individual);
    }

    public function getJsonSerializedEditObservation(Observation $observation)
    {
        $entityJsonSerialize = new EntityJsonSerialize();

        return $entityJsonSerialize->getJsonSerializedEditObservation($observation);
    }

    public function removeDuplicates(array $array): array
    {
        $removeDuplicates = [];
        foreach ($array as $value) {
            if (!in_array($value, $removeDuplicates)) {
                $removeDuplicates[] = $value;
            }
        }

        return $removeDuplicates;
    }

    public function displayEventDates(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        string $separator = '-'
    ): string {
        $startDateSplit = explode(
            '-',
            $startDate->format('Y-m-d')
        );
        $endDateSplit = explode(
            '-',
            $endDate->format('Y-m-d')
        );
        $pattern = 'd MMMM Y';
        $patternArray = explode(' ', $pattern);
        $patternArray = array_reverse($patternArray);
        $transDateTime = new HandleDateTime();
        $displayedEndDate = $transDateTime->dateTransFormat($pattern, $endDate);
        if ($startDateSplit === $endDateSplit) {
            return $displayedEndDate;
        }

        foreach ($startDateSplit as $key => $date) {
            if ($endDateSplit[$key] != $date) {
                $pattern = implode(
                    ' ',
                    array_reverse(
                        array_slice($patternArray, $key)
                    )
                );

                break;
            }
        }

        return $transDateTime->dateTransFormat($pattern, $startDate).' '.$separator.' '.$displayedEndDate;
    }

    public function setStationCards(array $stations): array
    {
        $observationsRepository = $this->manager->getRepository(Observation::class);
        $individualsRepository = $this->manager->getRepository(Individual::class);

        $lastStationsActivity = $this->getLastActivity($stations);
        $cards = [];
        foreach ($stations as $station) {
            $individuals = $individualsRepository->findAllIndividualsInStation($station);
            $observations = $observationsRepository->findAllObservationsInStation($station, $individuals);
            $contributorsCount = $observationsRepository->findAllObsContributorsCountInStation($individuals);

            $lastIndivActivity = $this->getLastActivity($individuals);
            $lastObsActivity = $this->getLastActivity($observations);
            $lastActivity = max($lastObsActivity, $lastIndivActivity);

            $cards[] = [
                'station' => $station,
                'observations' => $observations,
                'contributorsCount' => $contributorsCount,
                'lastActivity' => max($lastStationsActivity, $lastActivity),
            ];
        }

        return $cards;
    }

    public function getLastActivity(array $entityObjectsArray)
    {
        $lastActivity = null;
        foreach ($entityObjectsArray as $entityObject) {
            $entityObjectActivity = $entityObject->getUpdatedAt() ?? $entityObject->getCreatedAt();
            $lastActivity = max($lastActivity, $entityObjectActivity);
        }

        return $lastActivity;
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
                $eventsSpeciesForSingleSpecies = $this->getEventsSpeciesForSpecies($species);

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
                        && !$observation->getIsMissing()
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

    public function setObsChips(Individual $individual): array
    {
        $individualObservations = $this->manager->getRepository(Observation::class)
            ->findBy(['individual' => $individual], ['date' => 'DESC'])
        ;

        $validEvents = [];
        $eventsForSpecies = $this->getEventsSpeciesForSpecies($individual->getSpecies());
        foreach ($eventsForSpecies as $eventSpecies) {
            $validEvents[] = $eventSpecies->getEvent();
        }

        $observationsPerYear = [];
        foreach ($individualObservations as $observation) {
            if (in_array($observation->getEvent(), $validEvents)) {
                $year = date_format($observation->getDate(), 'Y');
                $i = array_search(
                    $year,
                    array_column($observationsPerYear, 'year')
                );
                if (false === $i) {
                    $observationsPerYear[] = [
                        'year' => $year,
                        'observations' => [$observation],
                    ];
                } else {
                    $observationsPerYear[$i]['observations'][] = $observation;
                }
            }
        }

        return $observationsPerYear;
    }

    public function getEventsSpeciesForSpecies(Species $species): array
    {
        return $this->manager->getRepository(EventSpecies::class)
            ->findBy(['species' => $species])
        ;
    }

    public function setSpeciesDisplayData(array $allSpecies): array
    {
        $speciesDisplayData = [];
        foreach ($allSpecies as $species) {
            $type = $species->getType();
            $i = array_search(
                $type,
                array_column($speciesDisplayData, 'type')
            );
            if (false === $i) {
                $speciesDisplayData[] = [
                    'type' => $type,
                    'typeSpecies' => [$species],
                ];
            } else {
                $speciesDisplayData[$i]['typeSpecies'][] = $species;
            }
        }

        return $speciesDisplayData;
    }
}
