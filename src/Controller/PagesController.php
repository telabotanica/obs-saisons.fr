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
            'lastArticles' => $postRepository->setPosts(Post::CATEGORY_NEWS)->findLastFeaturedPosts(),
            'lastEvents' => $postRepository->setPosts(Post::CATEGORY_EVENT)->findLastFeaturedPosts(),
            'featuredSpecies' => $featuredSpecies->getShuffledFeaturedSpecies(),
            'lastObservations' => $observationRepository->findLastObs(5),
            'lastObservationsWithImages' => $observationRepository->findLastObsWithImages(4),
            'obsCount' => $observationRepository->findObsCountThisYear(),
        ]);
    }

    /**
     * @Route("/a-propos", name="a-propos")
     */
    public function aPropos(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'a-propos']
        );

        return $this->render('pages/a-propos.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'page' => $page,
        ]);
    }

    /**
     * @Route("/participer", name="participer")
     */
    public function participer(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'participer']
        );

        return $this->render('pages/participer.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'page' => $page,
        ]);
    }

    /**
     * @Route("/participer/protocole", name="protocole")
     */
    public function protocole(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'protocole']
        );

        return $this->render('pages/protocole.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'page' => $page,
        ]);
    }

    /**
     * @Route("/outils-ressources", name="outils-ressources")
     */
    public function outilsRessources(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'outils-ressources']
        );

        return $this->render('pages/outils-ressources.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'page' => $page,
        ]);
    }

    /**
     * @Route("/relais", name="relais")
     */
    public function relais(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'relais']
        );

        return $this->render('pages/relais.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'page' => $page,
        ]);
    }
}
