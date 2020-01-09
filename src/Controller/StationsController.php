<?php

namespace App\Controller;

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
        $obsRepository = $this->getDoctrine()->getRepository(Observation::class);
        foreach ($stations as $station) {
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
            $obsInStation = $obsRepository->findLastObsInStation($station->getId());
            $footer =
            [
                [
                    'counter' => [
                        'icon' => 'person-icon',
                        'count' => $obsRepository->countObsContributors($obsInStation),
                    ],
                ],
                [
                    'avatars' => array_column($obsRepository->findObsImages($obsInStation), 'image'),
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
        $stationRepository = $this->getDoctrine()->getRepository(Station::class);
        $station = $stationRepository->findSationDataDisplayBySlug($slug);

        //die(var_dump($station));

        $activePageBreadCrumb = [
            'slug' => $slug,
            'title' => $station['name'],
        ];

        return $this->render('pages/station-page.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo(), $activePageBreadCrumb),
            'station' => $station,
            'route' => 'observations',
            'list_cards' => $this->setListCards($station),
        ]);
    }

    public function setListCards($station)
    {
        $list_cards = [];
        foreach ($station['species'] as $species) {
            $list_card = [
                'image' => $species['image'],
                'heading' => [
                    'title' => $species['name'],
                    'text' => $species['scientific_name'],
                ],
            ];

            if (isset($species['individuals'])) {
                $individuals_count = count($species['individuals']);
                $display_s_individuals = (1 === $individuals_count) ? '' : 's';
                $display_s_obs_count = (1 === $species['obs_count']) ? '' : 's';
                $list_card['details'] = [
                    'grey_text' => '<span class=indiv-count>'.$individuals_count.'</span> individu'.$display_s_individuals.' • <span class=obs-count>'.$species['obs_count'].'</span> observation'.$display_s_obs_count,
                ];
                if (!empty($species['last_obs_date']) && !empty($species['last_obs_stade'])) {
                    $list_card['details']['infos'] = [
                        'bolder' => $species['last_obs_stade'],
                        'lighter' => 'le '.$species['last_obs_date'],
                    ];
                }
                $list_card['calendar'] = [
                    'periods' => $species['periods'],
                    'individuals' => $species['individuals'],
                    'years' => $species['years'],
                ];
            }
            $list_cards[] = $list_card;
        }
        return $list_cards;
    }
}
