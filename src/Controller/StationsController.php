<?php

namespace App\Controller;

use App\DisplayData\StationDisplayData;
use App\Entity\Espece;
use App\Entity\Observation;
use App\Entity\Station;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class StationsController.
 */
class StationsController extends PagesController
{
    /* ************************************************ *
     * Stations
     * ************************************************ */

    /**
     * @Route("/participer/stations", name="stations")
     */
    public function stations(Request $request): Response
    {
        $stationRepository = $this->getDoctrine()->getRepository(Station::class);
        $stations = $stationRepository->findAll();
        // setting cards data
        /*$stationsDisplayData = new StationDisplayData($registry, $stations[0]);
        $data = $stationsDisplayData->getEscpecesIndividus();
        die(var_dump($data));*/

        $cards = $this->setStationCards($stations);

        return $this->render('pages/stations.html.twig', [
            'cards' => $cards,
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
        ]);
    }

    private function setStationCards(array $stations): array
    {
        $cards = [];
        $manager = $this->getDoctrine();
        $obsRepository = $manager->getRepository(Observation::class);
        foreach ($stations as $station) {
            $stationDisplayData = new StationDisplayData($station, $manager);
            $header = ['image' => $station->getHeaderImage()];
            if (!$station->getIsPublic()) {
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
            $countObsContributors = $stationDisplayData->getCountStationContributors();
            $footer = [
                [
                    'counter' => [
                        'icon' => 'person-icon',
                        'count' => $countObsContributors,
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
                'text' => 'Votre station n\'existe pas encore ?',
                'button' => 'Créer une station',
            ],
        ]);

        return $cards;
    }

    /* ************************************************ *
     * Station
     * ************************************************ */

    /**
     * @Route("/participer/stations/{slug}", name="stations_show")
     */
    public function stationPage(Request $request, string $slug): Response
    {
        $manager = $this->getDoctrine();
        $stationRepository = $this->getDoctrine()->getRepository(Station::class);
        $station = $stationRepository->findOneBy(['slug' => $slug]);
        $stationDisplayData = (new StationDisplayData($station, $manager))->setStationAllSpeciesDisplayData();

        $activePageBreadCrumb = [
            'slug' => $slug,
            'title' => $station->getName(),
        ];

        return $this->render('pages/station-page.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo(), $activePageBreadCrumb),
            'route' => 'observations',
            'station' => $station,
            'stationData' => $stationDisplayData,
            'list_cards' => $this->setListCards($stationDisplayData->getStationAllSpeciesDisplayData()),
        ]);
    }

    public function setListCards(array $stationAllSpeciesDisplayData)
    {
        $list_cards = [];
        foreach ($stationAllSpeciesDisplayData as $stationSpeciesDisplayData) {
            /**
             * @var Espece $species
             */
            $species = $stationSpeciesDisplayData->getSpecies();
            $list_card = [
                'image' => $species->getPhoto(),
                'heading' => [
                    'title' => $species->getNomVernaculaire(),
                    'text' => $species->getNomScientifique(),
                ],
            ];

            if (0 < $stationSpeciesDisplayData->getIndividualsCount()) {
                $list_card['calendar'] = $stationSpeciesDisplayData;

                $individuals_count = $stationSpeciesDisplayData->getIndividualsCount();
                $display_s_individuals = 's';
                $display_s_obs = 's';
                if (1 === $individuals_count) {
                    $display_s_individuals = '';
                }
                if (1 === $stationSpeciesDisplayData->getObsCount()) {
                    $display_s_obs = '';
                }
                $list_card['details'] = [
                    'grey_text' => '<span class=indiv-count>'.$individuals_count.'</span> individu'.$display_s_individuals.' • <span class=obs-count>'.$stationSpeciesDisplayData->getObsCount().'</span> observation'.$display_s_obs,
                ];
                if (!empty($stationSpeciesDisplayData->getLastObsDate()) && !empty($stationSpeciesDisplayData->getLastObsStade())) {
                    $list_card['details']['infos'] = [
                        'bolder' => $stationSpeciesDisplayData->getLastObsStade(),
                        'lighter' => 'le '.date_format($stationSpeciesDisplayData->getLastObsDate(), 'j/m/Y'),
                    ];
                }
            }
            $list_cards[] = $list_card;
        }

        return $list_cards;
    }
}
