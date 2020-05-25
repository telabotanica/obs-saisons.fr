<?php

// src/Twig/AppExtension.php

namespace App\Twig;

use App\DisplayData\Species\SpeciesDisplayData;
use App\DisplayData\Station\StationDisplayData;
use App\Entity\Event;
use App\Entity\Observation;
use App\Security\Voter\UserVoter;
use App\Service\SlugGenerator;
use Doctrine\Persistence\ManagerRegistry;
use IntlDateFormatter;
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

    public function __construct(Security $security, ManagerRegistry $manager)
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
            new TwigFunction('eventDatesDisplay', [$this, 'displayEventDates']),
            new TwigFunction('setStationListCards', [$this, 'setStationListCards']),
            new TwigFunction('setStationActionBarSquaredButton', [$this, 'setStationActionBarSquaredButton']),
            new TwigFunction('setStationCards', [$this, 'setStationCards']),
            new TwigFunction('setSpeciesPageAccordions', [$this, 'setSpeciesPageAccordions']),
        ];
    }

    /**
     * @return array|TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('slugify', [$this, 'slugify']),
            new TwigFilter('arrayUnique', [$this, 'arrayUnique']),
        ];
    }

    public function slugify(string $string): string
    {
        $slugGenerator = new SlugGenerator();

        return $slugGenerator->slugify($string);
    }

    public function arrayUnique(array $array): array
    {
        $arrayUnique = [];
        foreach ($array as $value) {
            if (!in_array($value, $arrayUnique)) {
                $arrayUnique[] = $value;
            }
        }

        return $arrayUnique;
    }

    public function displayEventDates(\DateTimeInterface $startDate, \DateTimeInterface $endDate, string $separator = '-'): string
    {
        $startDateSplit = explode('-', $startDate->format('Y-m-d'));
        $endDateSplit = explode('-', $endDate->format('Y-m-d'));
        $pattern = 'd MMMM Y';
        $patternArray = array_reverse(explode(' ', $pattern));
        $fmt = $this->fmtCreate($pattern);
        $displayedEndDate = datefmt_format($fmt, $endDate);

        if ($startDateSplit === $endDateSplit) {
            return $displayedEndDate;
        }

        foreach ($startDateSplit as $key => $date) {
            if ($endDateSplit[$key] != $date) {
                $pattern = implode(' ', array_reverse(array_slice($patternArray, $key)));
                //die(dump($pattern));
                break;
            }
        }
        $fmt = $this->fmtCreate($pattern);

        return datefmt_format($fmt, $startDate).' '.$separator.' '.$displayedEndDate;
    }

    public function getStationDisplayData($station)
    {
        return (new StationDisplayData($station, $this->manager))->setStationAllSpeciesDisplayData();
    }

    public function setStationCards(array $stations): array
    {
        $cards = [];
        foreach ($stations as $station) {
            $stationDisplayData = $this->getStationDisplayData($station);
            $stationImage = $station->getHeaderImage() ?? '/media/layout/image-placeholder.svg';
            $header = ['image' => $stationImage];
            if ($station->getIsPrivate()) {
                $header['icon_name'] = 'private';
            }
            $body = [
                'heading' => $station->getName(),
                'rows' => [
                    [
                        'icon_name' => 'pointer',
                        'text' => $station->getLocality(),
                    ],
                    [
                        'icon_name' => 'leaf',
                        'text' => $station->getHabitat(),
                    ],
                ],
            ];
            $footer = [
                [
                    'counter' => [
                        'icon' => 'person-icon',
                        'count' => $stationDisplayData->getCountStationContributors(),
                    ],
                ],
                [
                    'avatars' => $stationDisplayData->getStationObsImages(),
                ],
            ];
            $cards[] = [
                'tab_reference' => $station->getUser()->getId(),
                'card_link' => $station->getSlug(),
                'header' => $header,
                'body' => $body,
                'footer' => $footer,
            ];
        }
        array_unshift($cards, [
            'add' => [
                'icon_name' => 'add-pointer',
                'text' => 'Votre station n’existe pas encore ?',
                'button' => [
                    'label' => 'Créer une station',
                    'classes' => ['open'],
                    'dataAttributes' => [
                        [
                            'name' => 'open',
                            'value' => 'station',
                        ],
                        [
                            'name' => 'req-login',
                            'value' => true,
                        ],
                    ],
                ],
            ],
        ]);

        return $cards;
    }

    public function setStationListCards($stationAllSpeciesDisplayData): array
    {
        $listCards = [];
        foreach ($stationAllSpeciesDisplayData as $stationSpeciesDisplayData) {
            $species = $stationSpeciesDisplayData->species;
            $listCard = [
                'image' => '/media/species/'.$species->getPicture().'.jpg',
                'heading' => [
                    'title' => ucfirst($species->getVernacularName()),
                    'text' => $species->getScientificName(),
                ],
            ];
            $individuals = $stationSpeciesDisplayData->stationIndividuals;
            if (!empty($individuals)) {
                $listCard['calendar'] = $stationSpeciesDisplayData;

                $observations = $stationSpeciesDisplayData->stationSpeciesObservations;
                $lastObservation = reset($observations);

                $individualsCount = count($individuals);
                $obsCount = count(array_filter($observations, function (Observation $obs) {
                    return !$obs->getIsMissing();
                }));

                $display_s_individuals = 1 !== $individualsCount ? 's' : '';
                $display_s_obs = 1 !== $obsCount ? 's' : '';

                $listCard['details'] = [
                    'grey_text' => '<span class=indiv-count>'.$individualsCount.'</span> individu'.$display_s_individuals.' • <span class=obs-count>'.$obsCount.'</span> observation'.$display_s_obs,
                ];
                if (0 < $obsCount && !empty($lastObservation) && $lastObservation instanceof Observation) {
                    $lastObsDate = $lastObservation->getDate();
                    $lastObsStade = Event::DISPLAY_LABELS[$lastObservation->getEvent()->getName()];
                    $listCard['details']['infos'] = [
                        'bolder' => $lastObsStade,
                        'lighter' => 'le '.date_format($lastObsDate, 'j/m/Y'),
                    ];
                }
                $listCard['buttons'] = [
                    [
                        'icon' => 'help-circle',
                        'label' => 'fiche',
                    ],
                    [
                        'icon' => 'edit-calendar',
                        'action' => 'open',
                        'data_attr' => [
                            [
                                'name' => 'open',
                                'value' => 'observation',
                            ],
                            [
                                'name' => 'species',
                                'value' => $species->getId(),
                            ],
                            [
                                'name' => 'species-name',
                                'value' => $species->getVernacularName(),
                            ],
                            [
                                'name' => 'indiv',
                                'value' => implode(',', array_column($individuals, 'id')),
                            ],
                            [
                                'name' => 'req-login',
                                'value' => 'true',
                            ],
                        ],
                        'label' => 'saisir',
                    ],
                ];
            }
            $listCards[] = $listCard;
        }

        return $listCards;
    }

    public function setStationActionBarSquaredButton(StationDisplayData $stationDisplayData): array
    {
        $stationAllSpeciesIds = !empty($stationDisplayData->stationAllSpecies) ? implode(',', array_column($stationDisplayData->stationAllSpecies, 'id')) : '';
        $actionBarButtonClassAttributes = ['open', 'open-individual-form-all-station'];
        if (!$this->security->isGranted(UserVoter::LOGGED)) {
            $actionBarButtonClassAttributes[] = 'disabled';
        }

        return [
            'classes' => $actionBarButtonClassAttributes,
            'dataAttributes' => [
                [
                    'name' => 'open',
                    'value' => 'individual',
                ],
                [
                    'name' => 'species',
                    'value' => $stationAllSpeciesIds,
                ],
                [
                    'name' => 'all-species',
                    'value' => true,
                ],
                [
                    'name' => 'station',
                    'value' => $stationDisplayData->station->getId(),
                ],
                [
                    'name' => 'req-login',
                    'value' => 'true',
                ],
            ],
        ];
    }

    public function setSpeciesPageAccordions(): array
    {
        $accordions = [];
        $speciesDisplayData = new SpeciesDisplayData($this->manager);
        $types = $speciesDisplayData->getTypes();

        foreach ($types as $type) {
            $allSpecies = $speciesDisplayData->filterSpeciesByType($type);
            $contents = [];
            foreach ($allSpecies as $species) {
                $contents[] = [
                    'type' => 'include',
                    'include_uri' => 'components/list-cards.html.twig',
                    'include_object_name' => 'listCard',
                    'data' => [
                        'image' => '/media/species/'.$species->getPicture().'.jpg',
                        'heading' => [
                            'is_link' => true,
                            'href' => '', // add your link here
                            'title' => $species->getVernacularName(),
                            'text' => $species->getScientificName(),
                        ],
                    ],
                ];
            }

            $accordions[] = [
                'tab_reference' => $type->getReign(),
                'title' => $type->getName(),
                'contents' => $contents,
            ];
        }

        return $accordions;
    }

    private function fmtCreate(string $pattern): IntlDateFormatter
    {
        return datefmt_create(
            'fr_FR',
            null,
            null,
            null,
            null,
            $pattern
        );
    }
}
