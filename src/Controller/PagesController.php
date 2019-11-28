<?php

// src/Controller/PagesController.php

namespace App\Controller;

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
        $lastArticles = $this->getDoctrine()->getRepository(Post::class)->setPosts('article')->findLastFeaturedPosts();
        $lastEvents = $this->getDoctrine()->getRepository(Post::class)->setPosts('event')->findLastFeaturedPosts();

        return $this->render('pages/homepage.html.twig', [
            'lastArticles' => $lastArticles,
            'lastEvents' => $lastEvents,
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
     * @Route("/especes", name="especes")
     */
    public function especes(Request $request)
    {
        return $this->render('pages/especes.html.twig', [
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
