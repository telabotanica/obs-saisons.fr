<?php

namespace App\Controller;

use App\DisplayData\Station\StationDisplayData;
use App\Entity\Event;
use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Species;
use App\Entity\Station;
use App\Form\Type\IndividualType;
use App\Form\Type\ObservationType;
use App\Form\Type\StationType;
use App\Security\Voter\UserVoter;
use App\Service\SlugGenerator;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
     * @Route("/participer/stations", name="stations", methods={"GET", "POST"})
     */
    public function stations(Request $request): Response
    {
        $doctrine = $this->getDoctrine();
        $stationForm = $this->createForm(StationType::class);
        if ($request->isMethod('POST') && !$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }
        // setting cards data
        $cards = $this->setStationCards($doctrine->getRepository(Station::class)->findAll());

        if ($request->isMethod('POST') && 'station' === $request->request->get('action')) {
            if (!$this->isGranted(UserVoter::LOGGED)) {
                return $this->redirectToRoute('user_login');
            }

            $stationForm->handleRequest($request);
            if ($stationForm->isSubmitted() && $stationForm->isValid()) {
                $slugGenerator = new SlugGenerator();
                $station = new Station();
                $stationFormValues = $request->request->get('station');

                $station->setUser($this->getUser());
                $station->setName($stationFormValues['name']);
                $station->setSlug($slugGenerator->slugify($stationFormValues['name']));
                $station->setHabitat($stationFormValues['habitat']);
                $station->setDescription($stationFormValues['description']);
                $station->setIsPrivate(!empty($stationFormValues['is_private']));
                $station->setHeaderImage($this->uploadImage($request->files->get('station')['header_image']));
                $station->setLocality($stationFormValues['locality']);
                $station->setLatitude($stationFormValues['latitude']);
                $station->setLongitude($stationFormValues['longitude']);
                $station->setAltitude($stationFormValues['altitude']);
                $station->setInseeCode($stationFormValues['insee_code']);

                $entityManager = $doctrine->getManager();
                $entityManager->persist($station);
                $entityManager->flush();

                //return $this->redirect($request->getUri());
            }
        }

        return $this->render('pages/stations.html.twig', [
            'cards' => $cards,
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
            'stationForm' => $stationForm->createView(),
        ]);
    }

    private function setStationCards(array $stations): array
    {
        $cards = [];
        foreach ($stations as $station) {
            $stationDisplayData = new StationDisplayData($station, $this->getDoctrine());
            $stationImage = '/media/layout/image-placeholder.svg';
            if (null !== $station->getHeaderImage()) {
                $stationImage = $station->getHeaderImage();
            }
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

    /* ************************************************ *
     * Station
     * ************************************************ */

    /**
     * @Route("/participer/stations/{slug}", name="stations_show", methods={"GET", "POST"})
     */
    public function stationPage(Request $request, string $slug): Response
    {
        $doctrine = $this->getDoctrine();
        $individual = new Individual();
        $observation = new Observation();

        $stationRepository = $doctrine->getRepository(Station::class);
        $station = $stationRepository->findOneBy(['slug' => $slug]);
        $stationDisplayData = (new StationDisplayData($station, $doctrine))->setStationAllSpeciesDisplayData();

        $individualForm = $this->createForm(IndividualType::class, $individual, ['station_display_data' => $stationDisplayData]);
        $observationForm = $this->createForm(ObservationType::class, $observation, ['station_display_data' => $stationDisplayData]);

        $activePageBreadCrumb = [
            'slug' => $slug,
            'title' => $station->getName(),
        ];
        if ($request->isMethod('POST')) {
            if (!$this->isGranted(UserVoter::LOGGED)) {
                return $this->redirectToRoute('user_login');
            }
            switch ($request->request->get('action')) {
                case 'individual':
                    $individualForm->handleRequest($request);
                    if ($individualForm->isSubmitted() && $individualForm->isValid()) {
                        $individualFormValues = $request->request->get('individual');

                        $individual->setName($individualFormValues['name']);
                        $individual->setUser($this->getUser());
                        $individual->setStation($station);
                        $individual->setSpecies($doctrine->getRepository(Species::class)
                            ->find($individualFormValues['species'])
                        );
                        $entityManager = $doctrine->getManager();
                        $entityManager->persist($individual);
                        $entityManager->flush();
                    }
                    break;
                case 'observation':
                    $observationForm->handleRequest($request);
                    if ($observationForm->isSubmitted() && $observationForm->isValid()) {
                        $observationFormValues = $request->request->get('observation');

                        $observation->setUser($this->getUser());
                        $observation->setIndividual(
                            $doctrine->getRepository(Individual::class)
                                ->find($observationFormValues['individual'])
                        );
                        $observation->setEvent(
                            $doctrine->getRepository(Event::class)
                                ->find($observationFormValues['event'])
                        );
                        $observation->setDate(date_create($observationFormValues['date']));
                        $observation->setPicture($this->uploadImage($request->files->get('observation')['picture']));
                        $observation->setIsMissing(!empty($observationFormValues['is_missing']));
                        $observation->setDetails($observationFormValues['details']);

                        $entityManager = $doctrine->getManager();
                        $entityManager->persist($observation);
                        $entityManager->flush();
                    }
                    break;
                default:
                    break;
            }

            return $this->redirect($request->getUri());
        }

        return $this->render('pages/station-page.html.twig', [
            'list_cards' => $this->setListCards($stationDisplayData->stationAllSpeciesDisplayData),
            'station' => $station,
            'stationData' => $stationDisplayData,
            'squaredButtonData' => $this->setActionBarSquaredButtonData($stationDisplayData),
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo(), $activePageBreadCrumb),
            'route' => 'observations',
            'individualForm' => $individualForm->createView(),
            'observationForm' => $observationForm->createView(),
        ]);
    }

    public function setListCards(array $stationAllSpeciesDisplayData): array
    {
        $list_cards = [];
        foreach ($stationAllSpeciesDisplayData as $stationSpeciesDisplayData) {
            /**
             * @var Species
             */
            $species = $stationSpeciesDisplayData->getSpecies();
            $list_card = [
                'image' => '/media/species/'.$species->getPicture().'.jpg',
                'heading' => [
                    'title' => ucfirst($species->getVernacularName()),
                    'text' => $species->getScientificName(),
                ],
            ];

            if (0 < $stationSpeciesDisplayData->getIndividualsCount()) {
                $list_card['calendar'] = $stationSpeciesDisplayData;

                $individuals = [];
                foreach ($stationSpeciesDisplayData->getAllIndividualsObservationsDisplayData() as $individualDisplayData) {
                    $individuals[] = $individualDisplayData->getIndividual()->getId();
                }

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
                if (0 < $stationSpeciesDisplayData->getObsCount() && !empty($stationSpeciesDisplayData->getLastObsDate()) && !empty($stationSpeciesDisplayData->getLastObsStade())) {
                    $list_card['details']['infos'] = [
                        'bolder' => $stationSpeciesDisplayData->getLastObsStade(),
                        'lighter' => 'le '.date_format($stationSpeciesDisplayData->getLastObsDate(), 'j/m/Y'),
                    ];
                }
                $list_card['buttons'] = [
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
                                'value' => implode(',', $individuals),
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
            $list_cards[] = $list_card;
        }

        return $list_cards;
    }

    public function setActionBarSquaredButtonData(StationDisplayData $stationDisplayData): array
    {
        $stationAllSpeciesIdsArray = [];
        $stationAllSpeciesIds = '';
        if (!empty($stationDisplayData->stationAllSpecies)) {
            foreach ($stationDisplayData->stationAllSpecies as $species) {
                $stationAllSpeciesIdsArray[] = $species->getId();
            }
            $stationAllSpeciesIds = implode(',', $stationAllSpeciesIdsArray);
        }

        return [
            'classes' => ['open', 'open-individual-form-all-station'],
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

    private function uploadImage(?UploadedFile $formValue)
    {
        $fileName = null;
        if ($formValue) {
            $slugGenerator = new SlugGenerator();
            $imagesDirectoryPath = $this->getParameter('upload_destination');
            if (!is_dir($imagesDirectoryPath)) {
                mkdir($imagesDirectoryPath, 0777, true);
            }

            $file = $formValue;
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugGenerator->slugify($originalFilename);
            $fileName = $this->getParameter('images_uri_prefix').'/'.$safeFilename.'-'.uniqid().'.'.$file->guessExtension();

            try {
                $file->move(
                    $imagesDirectoryPath, // Le dossier dans le quel le fichier va etre charger
                    $fileName
                );
            } catch (FileException $e) {
                return new Response($e->getMessage());
            }
        }

        return $fileName;
    }
}
