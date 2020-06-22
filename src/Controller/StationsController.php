<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Species;
use App\Entity\Station;
use App\Entity\User;
use App\Form\Type\IndividualType;
use App\Form\Type\ObservationType;
use App\Form\Type\StationType;
use App\Security\Voter\UserVoter;
use App\Service\SlugGenerator;
use App\Service\UploadService;
use DateTime;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class StationsController.
 */
class StationsController extends PagesController
{
    public $uploadImageService;

    public function __construct(UploadService $uploadImageService)
    {
        parent::__construct();
        $this->uploadImageService = $uploadImageService;
    }

    /* ************************************************ *
     * Stations
     * ************************************************ */

    /**
     * @Route("/participer/stations", name="stations", methods={"GET", "POST"})
     */
    public function stations(Request $request): Response
    {
        $doctrine = $this->getDoctrine();
        $station = new Station();
        $stationForm = $this->createForm(StationType::class, $station);
        $stationRepository = $doctrine->getRepository(Station::class);

        if ($request->isMethod('POST') && !$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        if ($request->isMethod('POST') && 'station' === $request->request->get('action')) {
            if (!$this->isGranted(UserVoter::LOGGED)) {
                return $this->redirectToRoute('user_login');
            }
            $oldHeaderImage = null;
            if ('edit' === $request->request->get('action-type')) {
                $stationId = $request->request->get('station-id') ?? null;
                $station = $stationId ? $stationRepository->find($stationId) : null;
                if (!$station) {
                    throw $this->createNotFoundException('La station n’existe pas');
                }
                if (
                    $station->getIsPrivate()
                    && $station->getUser() !== $this->getUser()
                    && !$this->isGranted(User::ROLE_ADMIN)
                ) {
                    throw new AccessDeniedException('Vous n’êtes pas autorisé à contribuer sur cette station');
                }
                $oldHeaderImage = $station->getHeaderImage();
                $stationForm = $this->createForm(StationType::class, $station);
            }
            $stationForm->handleRequest($request);
            if ($stationForm->isSubmitted() && $stationForm->isValid()) {
                $slugGenerator = new SlugGenerator();
                $stationFormValues = $request->request->get('station');

                $dateNow = new DateTime('NOW');
                $name = $stationFormValues['name'];
                $habitat = $stationFormValues['habitat'];
                $description = $stationFormValues['description'];
                $is_private = !empty($stationFormValues['is_private']);
                $locality = $stationFormValues['locality'];
                $latitude = $stationFormValues['latitude'];
                $longitude = $stationFormValues['longitude'];
                $altitude = $stationFormValues['altitude'];
                $insee_code = $stationFormValues['insee_code'];
                $headerImage = $request->files->get('station')['header_image'];

                if ('new' === $request->request->get('action-type')) {
                    $station->setUser($this->getUser());
                    $station->setName($name);
                    $station->setSlug($slugGenerator->slugify($name));
                    $station->setHabitat($habitat);
                    $station->setDescription($description);
                    $station->setIsPrivate($is_private);
                    $station->setHeaderImage($this->uploadImageService->uploadImage($headerImage));
                    $station->setLocality($locality);
                    $station->setLatitude($latitude);
                    $station->setLongitude($longitude);
                    $station->setAltitude($altitude);
                    $station->setInseeCode($insee_code);
                    $station->setCreatedAt($dateNow);
                } else {
                    if ($name !== $station->getName()) {
                        $station->setName($name);
                        $station->setSlug($slugGenerator->slugify($name));
                    }
                    if ($habitat !== $station->getHabitat()) {
                        $station->setHabitat($habitat);
                    }
                    if ($description !== $station->getDescription()) {
                        $station->setDescription($description);
                    }
                    $isDeletePicture = 'true' === $request->request->get('is-delete-picture');
                    if ($headerImage || $isDeletePicture) {
                        if ($oldHeaderImage) {
                            $this->uploadImageService->deleteImage($oldHeaderImage);
                        }
                        if (!$isDeletePicture) {
                            $station->setHeaderImage($this->uploadImageService->uploadImage($headerImage));
                        } else {
                            $station->setHeaderImage(null);
                        }
                    } elseif ($oldHeaderImage) {
                        $station->setHeaderImage($oldHeaderImage);
                    }
                    if ($locality !== $station->getLocality()) {
                        $station->setLocality($locality);
                    }
                    if ($latitude !== $station->getLatitude()) {
                        $station->setLatitude($latitude);
                    }
                    if ($longitude !== $station->getLongitude()) {
                        $station->setLongitude($longitude);
                    }
                    if ($altitude !== $station->getAltitude()) {
                        $station->setAltitude($altitude);
                    }
                    if ($insee_code !== $station->getInseeCode()) {
                        $station->setInseeCode($insee_code);
                    }
                    $station->setUpdatedAt($dateNow);
                }
                $station->setIsPrivate($is_private);

                $entityManager = $doctrine->getManager();
                $entityManager->persist($station);
                $entityManager->flush();

                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'redirect' => $request->getUri(),
                    ]);
                }

                return $this->redirect($request->getUri());
            }
        }

        return $this->render('pages/stations.html.twig', [
            'stations' => $stationRepository->findAll(),
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'stationForm' => $stationForm->createView(),
        ]);
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
        $dateNow = new DateTime('NOW');

        $stationRepository = $doctrine->getRepository(Station::class);
        $individualRepository = $doctrine->getRepository(Individual::class);
        $observationRepository = $doctrine->getRepository(Observation::class);
        $speciesRepository = $doctrine->getRepository(Species::class);
        $eventRepository = $doctrine->getRepository(Event::class);
        $station = $stationRepository->findOneBy(['slug' => $slug]);
        if (!$station) {
            throw new \Exception('Station not found: '.$slug);
        }
        $stationAllIndividuals = $individualRepository->findSpeciesIndividualsForStation($station);

        $individualForm = $this->createForm(IndividualType::class, $individual, ['individuals' => $stationAllIndividuals]);
        $observationForm = $this->createForm(ObservationType::class, $observation, ['individuals' => $stationAllIndividuals]);

        $activePageBreadCrumb = [
            'slug' => $slug,
            'title' => $station->getName(),
        ];
        if ($request->isMethod('POST')) {
            if (!$this->isGranted(UserVoter::LOGGED)) {
                return $this->redirectToRoute('user_login');
            }
            if (
                $station->getIsPrivate()
                && $station->getUser() !== $this->getUser()
                && !$this->isGranted(User::ROLE_ADMIN)
            ) {
                throw new AccessDeniedException('Vous n’êtes pas autorisé à contribuer sur cette station');
            }

            switch ($request->request->get('action')) {
                case 'individual':
                    if ('edit' === $request->request->get('action-type')) {
                        $individualId = $request->request->get('individual-id') ?? null;
                        $individual = $individualId ? $individualRepository->find($individualId) : null;
                        if (!$individual) {
                            throw $this->createNotFoundException('L’individu n’existe pas');
                        }
                        $individualForm = $this->createForm(IndividualType::class, $individual, ['individuals' => $stationAllIndividuals]);
                    }
                    $individualForm->handleRequest($request);
                    if ($individualForm->isSubmitted() && $individualForm->isValid()) {
                        $individualFormValues = $request->request->get('individual');
                        $species = $speciesRepository->find($individualFormValues['species']);
                        $name = $individualFormValues['name'];
                        if ('new' === $request->request->get('action-type')) {
                            $individual->setUser($this->getUser());
                            $individual->setStation($station);
                            $individual->setCreatedAt($dateNow);
                            $individual->setName($name);
                            $individual->setSpecies($species);
                        } else {
                            $individual->setUpdatedAt($dateNow);
                            if ($individual->getName() !== $name) {
                                $individual->setName($name);
                            }
                            if ($individual->getSpecies() !== $species) {
                                $observations = $this->getDoctrine()->getRepository(Observation::class)
                                    ->findBy(['individual' => $individual], ['date' => 'DESC'])
                                ;
                                foreach ($observations as $observation) {
                                    $this->deleteEntityObject(Observation::class, $observation->getId());
                                }
                                $individual->setSpecies($species);
                            }
                        }
                        $entityManager = $doctrine->getManager();
                        $entityManager->persist($individual);
                        $entityManager->flush();
                    }
                    break;
                case 'observation':
                    $oldPicture = null;
                    if ('edit' === $request->request->get('action-type')) {
                        $observationId = $request->request->get('observation-id') ?? null;
                        $observation = $observationId ? $observationRepository->find($observationId) : null;
                        if (!$observation) {
                            throw $this->createNotFoundException('L’observation n’existe pas');
                        }
                        $oldPicture = $observation->getPicture();
                        $observationForm = $this->createForm(ObservationType::class, $observation, ['individuals' => $stationAllIndividuals]);
                    }
                    $observationForm->handleRequest($request);
                    if ($observationForm->isSubmitted() && $observationForm->isValid()) {
                        $observationFormValues = $request->request->get('observation');
                        $event = $eventRepository->find($observationFormValues['event']);
                        $date = date_create($observationFormValues['date']);
                        $picture = $request->files->get('observation')['picture'];
                        $details = $observationFormValues['details'];
                        $isMissing = !empty($observationFormValues['is_missing']);
                        if ('new' === $request->request->get('action-type')) {
                            $observation->setUser($this->getUser());
                            $observation->setIndividual(
                                $individualRepository->find($observationFormValues['individual'])
                            );
                            $observation->setEvent($event);
                            $observation->setDate($date);
                            $observation->setPicture($this->uploadImageService->uploadImage($picture));
                            $observation->setDetails($details);
                            $observation->setCreatedAt($dateNow);
                        } elseif ('edit' === $request->request->get('action-type')) {
                            if ($observation->getEvent() !== $event) {
                                $observation->setEvent($event);
                            }
                            if ($observation->getDate() !== $date) {
                                $observation->setDate($date);
                            }
                            if ($observation->getDetails() !== $details) {
                                $observation->setDetails($details);
                            }
                            $isDeletePicture = 'true' === $request->request->get('is-delete-picture');

                            if ($picture || $isDeletePicture) {
                                if ($oldPicture) {
                                    $this->uploadImageService->deleteImage($oldPicture);
                                }
                                if (!$isDeletePicture) {
                                    $observation->setPicture($this->uploadImageService->uploadImage($picture));
                                } else {
                                    $observation->setPicture(null);
                                }
                            } elseif ($oldPicture) {
                                $observation->setPicture($oldPicture);
                            }
                            $observation->setUpdatedAt($dateNow);
                        }
                        $observation->setIsMissing($isMissing);

                        $entityManager = $doctrine->getManager();
                        $entityManager->persist($observation);
                        $entityManager->flush();
                    }
                    break;
                default:
                    break;
            }
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'redirect' => $request->getUri(),
                ]);
            }

            return $this->redirect($request->getUri());
        }

        return $this->render('pages/station-page.html.twig', [
            'station' => $station,
            'individuals' => $stationAllIndividuals,
            'observations' => $observationRepository->findAllObservationsInStation($station, $stationAllIndividuals),
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo(), $activePageBreadCrumb),
            'individualForm' => $individualForm->createView(),
            'observationForm' => $observationForm->createView(),
        ]);
    }

    /**
     * @Route("/station/{stationId}/delete", name="station_delete")
     */
    public function stationDelete(Request $request, int $stationId): Response
    {
        /**
         * @var Station $station;
         */
        $station = $this->deleteEntityObject(Station::class, $stationId);

        if (!$station) {
            throw $this->createNotFoundException('La station n’existe pas');
        }

        $individuals = $this->getDoctrine()
            ->getRepository(Individual::class)
            ->findSpeciesIndividualsForStation($station)
        ;

        foreach ($individuals as $individual) {
            $this->deleteEntityObject(Individual::class, $individual->getId());
        }

        $this->addFlash('notice', 'La station a été supprimée');

        return $this->redirectToRoute('stations');
    }

    /**
     * @Route("/individual/{individualId}/delete", name="individual_delete")
     */
    public function individualDelete(Request $request, int $individualId): Response
    {
        /**
         * @var Individual $individual;
         */
        $individual = $this->deleteEntityObject(Individual::class, $individualId);

        if (!$individual) {
            throw $this->createNotFoundException('L’individu n’existe pas');
        }
        $observations = $this->getDoctrine()->getRepository(Observation::class)
            ->findBy(['individual' => $individual], ['date' => 'DESC'])
        ;

        foreach ($observations as $observation) {
            /*
             * @var Observation $observation;
             */
            $this->deleteEntityObject(Observation::class, $observation->getId());
        }

        $this->addFlash('notice', 'L’individu a été supprimé');

        return $this->redirectToRoute('stations_show', [
            'slug' => $individual->getStation()->getSlug(),
        ]);
    }

    /**
     * @Route("/observation/{obsId}/delete", name="observation_delete")
     */
    public function observationDelete(Request $request, int $obsId): Response
    {
        /**
         * @var Observation $obs;
         */
        $obs = $this->deleteEntityObject(Observation::class, $obsId);

        if (!$obs) {
            throw $this->createNotFoundException('Cette observation n’existe pas');
        }

        $this->addFlash('notice', 'L’observation a été supprimée');

        return $this->redirectToRoute('stations_show', [
            'slug' => $obs->getIndividual()->getStation()->getSlug(),
        ]);
    }

    /**
     * @return Station|Observation|Individual|null
     */
    private function deleteEntityObject(string $entity, int $id)
    {
        $doctrine = $this->getDoctrine();

        /**
         * @var Station|Observation|Individual|null $entityInstance;
         */
        $entityInstance = $doctrine->getRepository($entity)
            ->find($id)
        ;

        if (!$entityInstance) {
            return null;
        }

        $entityManager = $doctrine->getManager();
        $entityManager->remove($entityInstance);
        $entityManager->flush();

        return $entityInstance;
    }

    /**
     * @param Station|Observation $entityInstance
     *
     * @throws Exception
     */
    private function handleEditEntityInstanceDeletePicture(
        $entityInstance,
        ?bool $isDeletePicture,
        ?UploadedFile $picture,
        ?string $oldPicture
    ): void {
        if ($picture || $isDeletePicture) {
            if ($oldPicture) {
                $this->uploadImageService->deleteImage($oldPicture);
            }
            if (!$isDeletePicture) {
                $entityInstance->setPicture($this->uploadImageService->uploadImage($picture));
            } else {
                $entityInstance->setPicture(null);
            }
        }
    }
}
