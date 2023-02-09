<?php

namespace App\Controller;

use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Station;
use App\Form\IndividualType;
use App\Form\ObservationType;
use App\Form\StationType;
use App\Security\Voter\UserVoter;
use App\Service\BreadcrumbsGenerator;
use App\Service\EntityJsonSerialize;
use App\Service\Search;
use App\Service\SlugGenerator;
use App\Service\UploadService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class StationsController.
 */
class StationsController extends AbstractController
{
    /* ************************************************ *
     * Stations
     * ************************************************ */

    /**
     * @Route("/stations/{page<\d+>}", name="stations", methods={"GET"})
     */
    public function stations(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $manager,
        int $page = 1
    ) {
        $limit = 11;
        $station = new Station();
        $form = $this->createForm(StationType::class, $station, [
            'action' => $this->generateUrl('stations_new'),
        ]);
        $stationRepository = $manager->getRepository(Station::class);
        $lastPage = ceil($stationRepository->countStations() / $limit);

        return $this->render('pages/stations.html.twig', [
            'stations' => $stationRepository->findAllPaginatedOrderedStations($page, $limit),
            'breadcrumbs' => $breadcrumbsGenerator->setToRemoveFromPath('/'.$page)->getBreadcrumbs(),
            'stationForm' => $form->createView(),
            'pagination' => [
                'currentPage' => $page,
                'lastPage' => $lastPage,
            ],
        ]);
    }

    /**
     * @Route("/stations/mes-stations/{page<\d+>}", name="my_stations", methods={"GET"})
     */
    public function myStations(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $manager,
        int $page = 1
    ) {
        if (!$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        $limit = 11;
        $station = new Station();
        $form = $this->createForm(StationType::class, $station, [
            'action' => $this->generateUrl('stations_new'),
        ]);
        $user = $this->getUser();
        $stationRepository = $manager->getRepository(Station::class);
        $lastPage = ceil($stationRepository->countStations($user) / $limit);

        return $this->render('pages/stations.html.twig', [
            'headerMapLegend' => 'Mes stations',
            'stations' => $stationRepository->findAllPaginatedOrderedStations($page, $limit, $user),
            'dataStationsQuery' => 'user',
            'breadcrumbs' => $breadcrumbsGenerator->setToRemoveFromPath('/'.$page)
                ->setActiveTrail()
                ->getBreadcrumbs('stations'),
            'stationForm' => $form->createView(),
            'pagination' => [
                'currentPage' => $page,
                'lastPage' => $lastPage,
            ],
        ]);
    }

    /**
     * @Route("/stations/recherche", name="stations_search")
     */
    public function stationsSearch(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        Search $searchService
    ) {
        $searchTerm = $request->request->get('search-term');

        if (!$searchTerm) {
            return $this->redirectToRoute('stations');
        }

        return $this->render('pages/stations-search.html.twig', [
            'headerMapLegend' => 'Resultats de ma recherche',
            'stationsArray' => $searchService->stationsSearch($searchTerm),
            'search' => $searchTerm,
            'dataStationsQuery' => json_encode(['search' => $searchTerm]),
            'breadcrumbs' => $breadcrumbsGenerator->setActiveTrail()
                ->getBreadcrumbs('stations'),
        ]);
    }

    /**
     * @Route("/stations/mapInfo", name="stations_map_info", methods={"GET"})
     */
    public function stationsMapInfo(
        Request $request,
        EntityManagerInterface $manager,
        Search $searchService,
        EntityJsonSerialize $entityJsonSerialize
    ) {
        $stationRepository = $manager->getRepository(Station::class);
        $stations = null;

        if ($request->query->has('search')) {
            $stations = $searchService->stationsSearchRawResults($request->query->get('search'));
        } elseif ($request->query->has('user')) {
            $user = $this->getUser();
            if ($user) {
                $stations = $stationRepository->findBy(['user' => $user]);
            }
        } else {
            $stations = $stationRepository->findAll();
        }

        return new Response(
            $entityJsonSerialize->getJsonSerializedStationForListPageMap($stations),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }

    /**
     * @Route("/station/new", name="stations_new", methods={"POST"})
     */
    public function stationsNew(
        Request $request,
        EntityManagerInterface $manager,
        UploadService $uploadFileService
    ) {
        if (!$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        $station = new Station();
        $form = $this->createForm(StationType::class, $station);

        $form->handleRequest($request);

        $fileSize = 0;
        $oversize = false;

        if ($form->get('headerImage')->getData()){
            $fileSize = $form->get('headerImage')->getData()->getSize();
        }
        if($fileSize > 5242880){ $oversize = true ;};
	
		// Check if a station with the same name already exist, even if deleted
		$manager->getFilters()->disable('softdeleteable');
		$checkStationExist = $manager->getRepository(Station::class)
			->findOneBy(
				['name' => $station->getName(), 'locality' => $station->getLocality()]
			);
		$manager->getFilters()->enable('softdeleteable');
		if ($checkStationExist) {
			$this->addFlash('error', 'La station n’a pas pu être créée: Le nom existe déjà.');
		
			return $this->redirectToRoute('stations');
		}

        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement de l'image
            $image = null;
            $image = $form->get('headerImage')->getData();
            $previousHeaderImage = $station->getHeaderImage();

            $isDeletePicture = false;
            $image = $uploadFileService->setFile(
                $image,// input file data
                $previousHeaderImage,
                $isDeletePicture// removal requested
            );

            $station->setHeaderImage($image);

            $manager->persist($station);
            $manager->flush();

            $this->addFlash('success', 'Votre station a été créée');
        } elseif ($oversize){
            $this->addFlash('error', 'La station n’a pas pu être créée: votre image est trop lourde ! (5Mo maximum)');
        } else {
            $this->addFlash('error', 'La station n’a pas pu être créée');
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'redirect' => $this->generateUrl('stations_show', [
                    'slug' => $station->getSlug(),
                ]),
            ]);
        }

        return $this->redirectToRoute('stations_show', [
            'slug' => $station->getSlug(),
        ]);
    }

    /**
     * @Route("/station/{stationId}/edit", name="stations_edit", methods={"POST"})
     */
    public function stationsEdit(
        Request $request,
        EntityManagerInterface $manager,
        int $stationId,
        UploadService $uploadFileService
    ) {
        if (!$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        $station = $manager->getRepository(Station::class)
            ->find($stationId)
        ;
        if (!$station) {
            throw $this->createNotFoundException('la station n’existe pas');
        }
        $this->denyAccessUnlessGranted(
            'station:edit',
            $station,
            'Vous n’êtes pas autorisé à modifier cette station'
        );

        $form = $this->createForm(StationType::class, $station);

        $form->handleRequest($request);

        $fileSize = 0;
        $oversize = false;

        if ($form->get('headerImage')->getData()){
            $fileSize = $form->get('headerImage')->getData()->getSize();
        }
        if($fileSize > 5242880){ $oversize = true ;};

        if ($form->isSubmitted() && $form->isValid() && !$oversize) {

            // Traitement de l'image
            $image = null;
            $image = $form->get('headerImage')->getData();
            $previousHeaderImage = $station->getHeaderImage();

            $isDeletePicture = false;
            $image = $uploadFileService->setFile(
                $image,// input file data
                $previousHeaderImage,
                $isDeletePicture// removal requested
            );

            $station->setHeaderImage($image);

            $manager->flush();

            $this->addFlash('success', 'Votre station a été modifiée');
        } elseif ($oversize){
            $this->addFlash('error', 'La station n’a pas pu être modifiée: votre image est trop lourde ! (5Mo maximum)');
        } else {
            $this->addFlash('error', 'La station n’a pas pu être modifiée');
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'redirect' => $this->generateUrl('stations_show', [
                    'slug' => $station->getSlug(),
                ]),
            ]);
        }

        return $this->redirectToRoute('stations_show', [
            'slug' => $station->getSlug(),
        ]);
    }

    /**
     * @Route("/station/{stationId}/delete", name="station_delete")
     */
    public function stationDelete(
        EntityManagerInterface $manager,
        int $stationId
    ) {
        if (!$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        $station = $manager->getRepository(Station::class)
            ->find($stationId)
        ;
        if (!$station) {
            throw $this->createNotFoundException('La station n’existe pas');
        }
        $this->denyAccessUnlessGranted(
            'station:edit',
            $station,
            'Vous n’êtes pas autorisé à supprimer cette station'
        );

        // related individuals must also be removed
        $individuals = $manager->getRepository(Individual::class)
            ->findBy(['station' => $station])
        ;

        foreach ($individuals as $individual) {
            // related observations must also be removed
            $observations = $manager->getRepository(Observation::class)
                ->findBy(['individual' => $individual]);
            foreach ($observations as $observation) {
                $manager->remove($observation);
            }

            $manager->remove($individual);
        }

        $manager->remove($station);
        $manager->flush();

        $this->addFlash('notice', 'La station a été supprimée');

        return $this->redirectToRoute('my_stations');
    }

    /* ************************************************ *
     * Station
     * ************************************************ */

    /**
     * @Route("/stations/{slug}", name="stations_show", methods={"GET"})
     */
    public function stationPage(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $manager,
        string $slug
    ) {
        $station = $manager->getRepository(Station::class)
            ->findOneBy(['slug' => $slug])
        ;
        if (!$station) {
            throw $this->createNotFoundException('La station n’existe pas');
        }

        $stationForm = $this->createForm(StationType::class, $station, [
            'action' => $this->generateUrl('stations_new'),
        ]);

        $individuals = $manager->getRepository(Individual::class)
            ->findSpeciesIndividualsForStation($station)
        ;

        $individual = new Individual();
        $stationId = $station->getId();
        $individualForm = $this->createForm(IndividualType::class, $individual, [
            'action' => $this->generateUrl('individual_new', [
                'stationId' => $stationId,
            ]),
            'station' => $station,
        ]);

        $observation = new Observation();
        $observationForm = $this->createForm(ObservationType::class, $observation, [
            'action' => $this->generateUrl('observation_new', [
                'stationId' => $stationId,
            ]),
            'station' => $station,
        ]);

        $observations = $manager->getRepository(Observation::class)->findAllObservationsInStation($station, $individuals);

        return $this->render('pages/station-page.html.twig', [
            'station' => $station,
            'individuals' => $individuals,
            'observations' => $observations,
            'stationForm' => $stationForm->createView(),
            'individualForm' => $individualForm->createView(),
            'observationForm' => $observationForm->createView(),
            'breadcrumbs' => $breadcrumbsGenerator->setActiveTrail($slug, $station->getName())
                ->getBreadcrumbs('stations'),
        ]);
    }

    /**
     * @Route("station/{stationId}/individual/new", name="individual_new", methods={"POST"})
     *
     * @throws Exception
     */
    public function individualNew(
        Request $request,
        EntityManagerInterface $manager,
        int $stationId,
		SlugGenerator $slugGenerator
    ) {
        if (!$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }
        $station = $manager->getRepository(Station::class)
            ->find($stationId)
        ;
        if (!$station) {
            throw $this->createNotFoundException('La station n’a pas été trouvée');
        }
        $this->denyAccessUnlessGranted(
            'station:contribute',
            $station,
            'Vous n’êtes pas autorisé à contribuer sur cette station'
        );

        $individual = new Individual();
        $form = $this->createForm(IndividualType::class, $individual, [
            'station' => $station,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($individual);
            $manager->flush();

            $this->addFlash('success', 'Votre individu a été créé');
        } else {
            $this->addFlash('error', 'Votre individu n’a pas pu être créé');
        }
		
		$slugifiedName = $slugGenerator->slugify($individual->getSpecies()->getVernacularName());
		$slug = $station->getSlug().'#'.$slugifiedName;
		
		return $this->redirect('/stations/'.$station->getSlug().'#'.$slugifiedName);
    }

    /**
     * @Route("/individual/{individualId}/edit", name="individual_edit", methods={"POST"})
     */
    public function individualEdit(
        Request $request,
        EntityManagerInterface $manager,
        int $individualId,
		SlugGenerator $slugGenerator
    ) {
        if (!$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        $individual = $manager->getRepository(Individual::class)
            ->find($individualId)
        ;
        if (!$individual) {
            throw $this->createNotFoundException('L’individu n’existe pas');
        }
        $this->denyAccessUnlessGranted(
            'individual:edit',
            $individual,
            'Vous n’êtes pas autorisé à modifier ce individu'
        );

        $form = $this->createForm(IndividualType::class, $individual);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('success', 'Votre individu a été modifié');
        } else {
            $this->addFlash('error', 'L’individu n’a pas pu être modifié');
        }
	
		$slugifiedName = $slugGenerator->slugify($individual->getSpecies()->getVernacularName());
	
		return $this->redirect('/stations/'.$individual->getStation()->getSlug().'#'.$slugifiedName);
    }

    /**
     * @Route("/individual/{individualId}/delete", name="individual_delete")
     */
    public function individualDelete(
        EntityManagerInterface $manager,
        int $individualId,
		SlugGenerator $slugGenerator
    ) {
        if (!$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        $individual = $manager->getRepository(Individual::class)
            ->find($individualId)
        ;
        if (!$individual) {
            throw $this->createNotFoundException('L’individu n’existe pas');
        }
        $this->denyAccessUnlessGranted(
            'individual:edit',
            $individual,
            'Vous n’êtes pas autorisé à supprimer cet individu'
        );

        // related observations must also be removed
        $observations = $manager->getRepository(Observation::class)
            ->findBy(['individual' => $individual])
        ;
        foreach ($observations as $observation) {
            $manager->remove($observation);
        }

        $manager->remove($individual);
        $manager->flush();
	
		$slugifiedName = $slugGenerator->slugify($individual->getSpecies()->getVernacularName());

        $this->addFlash('notice', 'Votre individu a été supprimé');
	
		return $this->redirect('/stations/'.$individual->getStation()->getSlug().'#'.$slugifiedName);
    }

    /**
     * @Route("station/{stationId}/observation/new", name="observation_new", methods={"POST"})
     *
     * @throws Exception
     */
    public function observationNew(
        Request $request,
        EntityManagerInterface $manager,
        int $stationId,
        UploadService $uploadFileService,
		SlugGenerator $slugGenerator
    ) {
        if (!$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        $station = $manager->getRepository(Station::class)
            ->find($stationId)
        ;
        if (!$station) {
            throw $this->createNotFoundException('La station n’a pas été trouvée');
        }
        $this->denyAccessUnlessGranted(
            'station:contribute',
            $station,
            'Vous n’êtes pas autorisé à contribuer sur cette station'
        );

        $observation = new Observation();
        $form = $this->createForm(ObservationType::class, $observation, [
            'station' => $station,
        ]);

        $form->handleRequest($request);

        $fileSize = 0;
        $oversize = false;

        if ($form->get('picture')->getData()){
            $fileSize = $form->get('picture')->getData()->getSize();
        }
        if($fileSize > 5242880){ $oversize = true ;};

        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement de l'image
            $image = null;
            $image = $form->get('picture')->getData();
            $previousHeaderImage = $station->getHeaderImage();

            $isDeletePicture = false;
            $image = $uploadFileService->setFile(
                $image,// input file data
                $previousHeaderImage,
                $isDeletePicture// removal requested
            );

            $observation->setPicture($image);

            $manager->persist($observation);
            $manager->flush();

            $this->addFlash('success', 'Votre observation a été créée');
        } elseif ($oversize){
            $this->addFlash('error', "Votre observation n'a pas pu être créée: votre image est trop lourde ! (5Mo maximum)");
        } else {
            $this->addFlash('error', "Votre observation n'a pas pu être créée");
        }

		$individual=$observation->getIndividual();
		$slugifiedName = $slugGenerator->slugify($individual->getSpecies()->getVernacularName());

        $redirect = $this->generateUrl('stations_show', [
            'slug' => $station->getSlug(),
        ]);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'redirect' => $redirect,
            ]);
        }

		return $this->redirect('/stations/'.$individual->getStation()->getSlug().'#'.$slugifiedName);
    }

    /**
     * @Route("/observation/{observationId}/edit", name="observation_edit", methods={"POST"})
     */
    public function observationEdit(
        Request $request,
        EntityManagerInterface $manager,
        int $observationId,
        UploadService $uploadFileService,
		SlugGenerator $slugGenerator
    ) {
        if (!$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        $observation = $manager->getRepository(Observation::class)
            ->find($observationId)
        ;
        if (!$observation) {
            throw $this->createNotFoundException('L’observation n’existe pas');
        }
        $this->denyAccessUnlessGranted(
            'observation:edit',
            $observation,
            'Vous n’êtes pas autorisé à modifier cette observation'
        );

        $station = $observation->getIndividual()->getStation();
        $form = $this->createForm(ObservationType::class, $observation, [
            'station' => $station,
        ]);

        $form->handleRequest($request);

        $fileSize = 0;
        $oversize = false;

        if ($form->get('picture')->getData()){
            $fileSize = $form->get('picture')->getData()->getSize();
        }
        if($fileSize > 5242880){ $oversize = true ;};

        if ($form->isSubmitted() && $form->isValid()) {
            // Traitement de l'image
            $image = null;
            $image = $form->get('picture')->getData();
            $previousHeaderImage = $station->getHeaderImage();

            $isDeletePicture = false;
            $image = $uploadFileService->setFile(
                $image,// input file data
                $previousHeaderImage,
                $isDeletePicture// removal requested
            );

            $observation->setPicture($image);

            $manager->flush();

            $this->addFlash('success', 'Votre observation a été modifiée');
        } elseif ($oversize){
            $this->addFlash('error', "Votre observation n'a pas pu être modifiée: votre image est trop lourde ! (5Mo maximum)");
        } else {
            $this->addFlash('error', "Votre observation n'a pas pu être modifiée");
        }
	
		$individual=$observation->getIndividual();
		$slugifiedName = $slugGenerator->slugify($individual->getSpecies()->getVernacularName());

        $redirect = $this->generateUrl('stations_show', [
            'slug' => $station->getSlug(),
        ]);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'redirect' => $redirect,
            ]);
        }
	
		return $this->redirect('/stations/'.$individual->getStation()->getSlug().'#'.$slugifiedName);
    }

    /**
     * @Route("/observation/{observationId}/delete", name="observation_delete")
     */
    public function observationDelete(
        EntityManagerInterface $manager,
        int $observationId,
		SlugGenerator $slugGenerator
    ) {
        if (!$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        $observation = $manager->getRepository(Observation::class)
            ->find($observationId)
        ;
        if (!$observation) {
            throw $this->createNotFoundException('L’observation n’existe pas');
        }
        $this->denyAccessUnlessGranted(
            'observation:edit',
            $observation,
            'Vous n’êtes pas autorisé à supprimer cette observation'
        );

        $manager->remove($observation);
        $manager->flush();
		
		$individual=$observation->getIndividual();
		$slugifiedName = $slugGenerator->slugify($individual->getSpecies()->getVernacularName());
		

        $this->addFlash('notice', 'Votre observation a été supprimée');
	
		return $this->redirect('/stations/'.$individual->getStation()->getSlug().'#'.$slugifiedName);
    }
}
