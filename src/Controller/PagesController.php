<?php

// src/Controller/PagesController.php

namespace App\Controller;

use App\Entity\Observation;
use App\Entity\Post;
use App\Service\BreadcrumbsGenerator;
use App\Service\FeaturedSpecies;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PagesController.
 */
class PagesController extends AbstractController
{
    /**
     * Index action.
     *
     * @Route("/", name="homepage")
     */
    public function index(
        EntityManagerInterface $manager,
        FeaturedSpecies $featuredSpecies
    ) {
        $postRepository = $manager->getRepository(Post::class);
        $observationRepository = $manager->getRepository(Observation::class);

        return $this->render('pages/homepage.html.twig', [
            'lastArticles' => $postRepository->setPosts('article')->findLastFeaturedPosts(),
            'lastEvents' => $postRepository->setPosts('event')->findLastFeaturedPosts(),
            'featuredSpecies' => $featuredSpecies->getShuffledFeaturedSpecies(),
            'lastObservations' => $observationRepository->findLastObs(5),
            'lastObservationsWithImages' => $observationRepository->findLastObsWithImages(4),
            'obsCount' => $observationRepository->findObsCountThisYear(),
        ]);
    }

    /**
     * @Route("/apropos", name="apropos")
     */
    public function apropos(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator
    ) {
        return $this->render('pages/apropos.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }

    /**
     * @Route("/participer", name="participer")
     */
    public function participer(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator
    ) {
        return $this->render('pages/participer.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }

    /**
     * @Route("/participer/protocole", name="protocole")
     */
    public function protocole(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator
    ) {
        return $this->render('pages/protocole.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }

    /**
     * @Route("/resultats", name="resultats")
     */
    public function resultats(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator
    ) {
        return $this->render('pages/resultats.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }

    /**
     * @Route("/outils-ressources", name="outils-ressources")
     */
    public function outilsRessources(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator
    ) {
        return $this->render('pages/outils-ressources.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }

    /**
     * @Route("/relais", name="relais")
     */
    public function relais(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator
    ) {
        return $this->render('pages/relais.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }
}
