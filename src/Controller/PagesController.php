<?php

// src/Controller/PagesController.php

namespace App\Controller;

use App\Entity\Observation;
use App\Entity\Post;
use App\Service\BreadcrumbsGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/", name="homepage")
     */
    public function index()
    {
        $manager = $this->getDoctrine();
        $postRepository = $manager->getRepository(Post::class);
        $observationRepository = $manager->getRepository(Observation::class);

        return $this->render('pages/homepage.html.twig', [
            'lastArticles' => $postRepository->setPosts('article')->findLastFeaturedPosts(),
            'lastEvents' => $postRepository->setPosts('event')->findLastFeaturedPosts(),
            'lastObservations' => $observationRepository->findLastObsWithImages(5),
            'obsCount' => $observationRepository->findObsCountThisYear(),
            'randomObservations' => $observationRepository->findRandomObs(4),
        ]);
    }

    /**
     * @Route("/apropos", name="apropos")
     */
    public function apropos(Request $request)
    {
        return $this->render('pages/apropos.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
        ]);
    }

    /**
     * @Route("/participer", name="participer")
     */
    public function participer(Request $request)
    {
        return $this->render('pages/participer.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
        ]);
    }

    /**
     * @Route("/resultats", name="resultats")
     */
    public function resultats(Request $request)
    {
        return $this->render('pages/resultats.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
        ]);
    }

    /**
     * @Route("/outils-ressources", name="outils-ressources")
     */
    public function outilsRessources(Request $request)
    {
        return $this->render('pages/outils-ressources.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
        ]);
    }

    /**
     * @Route("/relais", name="relais")
     */
    public function relais(Request $request)
    {
        return $this->render('pages/relais.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
        ]);
    }
}
