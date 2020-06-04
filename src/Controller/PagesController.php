<?php

// src/Controller/PagesController.php

namespace App\Controller;

use App\Entity\Observation;
use App\Entity\Post;
use App\Service\BreadcrumbsGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PagesController.
 */
class PagesController extends AbstractController
{
    /**
     * @var BreadcrumbsGenerator
     */
    public $breadcrumbsGenerator;

    /**
     * PagesController constructor.
     */
    public function __construct()
    {
        $this->breadcrumbsGenerator = new BreadcrumbsGenerator();
    }

    /**
     * Index action.
     * @Route("/", name="homepage")
     */
    public function index(): Response
    {
        $manager = $this->getDoctrine();
        $postRepository = $manager->getRepository(Post::class);
        $observationRepository = $manager->getRepository(Observation::class);

        return $this->render('pages/homepage.html.twig', [
            'lastArticles' => $postRepository->setPosts('article')->findLastFeaturedPosts(),
            'lastEvents' => $postRepository->setPosts('event')->findLastFeaturedPosts(),
            'lastObservations' => $observationRepository->findLastObs(5),
            'lastObservationsWithImages' => $observationRepository->findLastObsWithImages(4),
            'obsCount' => $observationRepository->findObsCountThisYear(),
        ]);
    }

    /**
     * @Route("/apropos", name="apropos")
     */
    public function apropos(Request $request): Response
    {
        return $this->render('pages/apropos.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }

    /**
     * @Route("/participer", name="participer")
     */
    public function participer(Request $request): Response
    {
        return $this->render('pages/participer.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }

    /**
     * @Route("/participer/protocole", name="protocole")
     */
    public function protocole(Request $request): Response
    {
        return $this->render('pages/protocole.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }

    /**
     * @Route("/resultats", name="resultats")
     */
    public function resultats(Request $request): Response
    {
        return $this->render('pages/resultats.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }

    /**
     * @Route("/outils-ressources", name="outils-ressources")
     */
    public function outilsRessources(Request $request): Response
    {
        return $this->render('pages/outils-ressources.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }

    /**
     * @Route("/relais", name="relais")
     */
    public function relais(Request $request): Response
    {
        return $this->render('pages/relais.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }
}
