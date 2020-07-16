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
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/participer/stations", name="stations", methods={"GET"})
     */
    public function stations(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $manager
    ) {
        $station = new Station();
        $form = $this->createForm(StationType::class, $station, [
            'action' => $this->generateUrl('stations_new'),
        ]);

        return $this->render('pages/stations.html.twig', [
            'stations' => $manager->getRepository(Station::class)->findAll(),
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'stationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/station/new", name="stations_new", methods={"POST"})
     */
    public function stationsNew(
        Request $request,
        EntityManagerInterface $manager
    ) {
        if (!$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        $station = new Station();
        $form = $this->createForm(StationType::class, $station);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($station);
            $manager->flush();

            $this->addFlash('success', 'Votre station a été créée');
        } else {
            $this->addFlash('error', 'Votre station n’a pas pu être créée');
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'redirect' => $this->generateUrl('stations'),
            ]);
        }

        return $this->redirectToRoute('stations');
    }

    /**
     * @Route("/station/{stationId}/edit", name="stations_edit", methods={"POST"})
     */
    public function stationsEdit(
        Request $request,
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
            throw $this->createNotFoundException('la station n’existe pas');
        }
        $this->denyAccessUnlessGranted(
            'station:edit',
            $station,
            'Vous n’êtes pas autorisé à modifier cette station'
        );

        $form = $this->createForm(StationType::class, $station);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('success', 'Votre station a été modifiée');
        } else {
            $this->addFlash('error', 'La station n’a pas pu être modifiée');
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'redirect' => $this->generateUrl('stations'),
            ]);
        }

        return $this->redirectToRoute('stations');
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
            ->findBy(['individual' => $station])
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

        return $this->redirectToRoute('stations');
    }

    /* ************************************************ *
     * Station
     * ************************************************ */

    /**
     * @Route("/participer/stations/{slug}", name="stations_show", methods={"GET"})
     */
    public function stationPage(
        Request $request,
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

        $breadcrumbs = $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo(), [
            'slug' => $slug,
            'title' => $station->getName(),
        ]);

        return $this->render('pages/station-page.html.twig', [
            'station' => $station,
            'individuals' => $individuals,
            'observations' => $observations,
            'individualForm' => $individualForm->createView(),
            'observationForm' => $observationForm->createView(),
            'breadcrumbs' => $breadcrumbs,
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
        int $stationId
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

        return $this->redirectToRoute('stations_show', [
            'slug' => $station->getSlug(),
        ]);
    }

    /**
     * @Route("/individual/{individualId}/edit", name="individual_edit", methods={"POST"})
     */
    public function individualEdit(
        Request $request,
        EntityManagerInterface $manager,
        int $individualId
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

        return $this->redirectToRoute('stations_show', [
            'slug' => $individual->getStation()->getSlug(),
        ]);
    }

    /**
     * @Route("/individual/{individualId}/delete", name="individual_delete")
     */
    public function individualDelete(
        EntityManagerInterface $manager,
        int $individualId
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

        $this->addFlash('notice', 'Votre individu a été supprimé');

        return $this->redirectToRoute('stations_show', [
            'slug' => $individual->getStation()->getSlug(),
        ]);
    }

    /**
     * @Route("station/{stationId}/observation/new", name="observation_new", methods={"POST"})
     *
     * @throws Exception
     */
    public function observationNew(
        Request $request,
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
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($observation);
            $manager->flush();

            $this->addFlash('success', 'Votre observation a été créée');
        } else {
            $this->addFlash('error', 'Votre observation n’a pas pu être créée');
        }

        $redirect = $this->generateUrl('stations_show', [
            'slug' => $station->getSlug(),
        ]);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'redirect' => $redirect,
            ]);
        }

        return $this->redirect($redirect);
    }

    /**
     * @Route("/observation/{observationId}/edit", name="observation_edit", methods={"POST"})
     */
    public function observationEdit(
        Request $request,
        EntityManagerInterface $manager,
        int $observationId
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
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('success', 'Votre observation a été modifiée');
        } else {
            $this->addFlash('error', 'L’observation n’a pas pu être modifiée');
        }

        $redirect = $this->generateUrl('stations_show', [
            'slug' => $station->getSlug(),
        ]);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'redirect' => $redirect,
            ]);
        }

        return $this->redirect($redirect);
    }

    /**
     * @Route("/observation/{observationId}/delete", name="observation_delete")
     */
    public function observationDelete(
        EntityManagerInterface $manager,
        int $observationId
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

        $this->addFlash('notice', 'Votre observation a été supprimée');

        return $this->redirectToRoute('stations_show', [
            'slug' => $observation->getIndividual()->getStation()->getSlug(),
        ]);
    }
}
