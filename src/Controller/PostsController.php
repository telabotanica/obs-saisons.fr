<?php

namespace App\Controller;

use App\Entity\Post;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PostsController.
 */
class PostsController extends PagesController
{
    /* ************************************************ *
     * actualites
     * ************************************************ */
    /**
     * @Route("/actualites/{page<\d+>}", name="actualites")
     */
    public function actualitesIndex(
        Request $request,
        int $page = 1
    ) {
        $articleRepository = $this->getDoctrine()->getRepository(Post::class)->setPosts('article');
        $articles = $articleRepository->findAll();

        return $this->render('pages/actualites.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs(str_replace('/'.$page, '', $request->getPathInfo())),
            'route' => 'actualites',
            'articles' => $articles,
            'pagination' => [
                'currentPage' => $page,
                'lastPage' => 10
            ]
        ]);
    }

    /* ************************************************ *
     * article
     * ************************************************ */
    /**
     * @Route("/actualites/{slug<\d*\/\d*\/.+>}", name="actualites_show")
     */
    public function articlePage(
        Request $request,
        string $slug
    ) {
        $articleRepository = $this->getDoctrine()->getRepository(Post::class)->setPosts('article');
        $article = $articleRepository->findBySlug($slug);
        // @TODO: redirect to 404 page
        if (null === $article) {
            return $this->index($manager);
        }

        $nextPreviousArticles = $articleRepository->findNextPrevious($article->getId());

        $activePageBreadCrumb = [
            'slug' => $slug,
            'title' => $article->getTitle()
        ];

        return $this->render('pages/article.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo(), $activePageBreadCrumb),
            'article' => $article,
            'nextPreviousArticles' => $nextPreviousArticles,
        ]);
    }

    /* ************************************************ *
     * evenements
     * ************************************************ */
     /**
     * @Route("/evenements/{page<\d+>}", name="evenements")
     */
    public function evenementsIndex(
        Request $request,
        int $page = 1
    ) {
        $eventRepository = $this->getDoctrine()->getRepository(Post::class)->setPosts('event');
        $events = $eventRepository->findAll();

        return $this->render('pages/evenements.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs(str_replace('/'.$page, '', $request->getPathInfo())),
            'route' => 'evenements',
            'events' => $events,
            'pagination' => [
                'currentPage' => $page,
                'lastPage' => 10
            ]
        ]);
    }

    /* ************************************************ *
     * event
     * ************************************************ */

    /**
     * @Route("/evenements/{slug<\d*\/?\d*\/?.+>}", name="evenements_show")
     */
    public function eventPage(
        Request $request,
        string $slug
    ) {
        $eventRepository = $this->getDoctrine()->getRepository(Post::class)->setPosts('event');
        $event = $eventRepository->findBySlug($slug);
        // @TODO: redirect to 404 page
        if (null === $event) {
            return $this->index($manager);
        }

        $nextPreviousEvents = $eventRepository->findNextPrevious($event->getId());

        $activePageBreadCrumb = [
            'slug' => $slug,
            'title' => $event->getTitle()
        ];

        return $this->render('pages/event.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo(), $activePageBreadCrumb),
            'event' => $event,
            'nextPreviousEvents' => $nextPreviousEvents,
        ]);
    }
}
