<?php

namespace App\Controller;

use App\Entity\News;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class NewsController.
 */
class NewsController extends PagesController
{
    /* ************************************************ *
     * actualites
     * ************************************************ */

    /**
     * @Route("/actualites", name="actualites")
     */
    public function actualitesIndex(
        Request $request
    ) {
        $articleRepository = $this->getDoctrine()->getRepository(News::class)->setCategory('article');
        $articles = $articleRepository->findAll();

        return $this->render('pages/actualites.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
            'articles' => $articles,
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
        $articleRepository = $this->getDoctrine()->getRepository(News::class)->setCategory('article');
        $article = $articleRepository->findBySlug($slug);
        // todo: redirect to 404 page
        if (null === $article) {
            return $this->index($manager);
        }

        $nextPreviousArticles = $articleRepository->findNextPrevious($article->getId());

        $activePageBreadCrumb[$slug] = $article->getTitle();

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
     * @Route("/evenements", name="evenements")
     */
    public function evenementsIndex(
        Request $request
    ) {
        $eventRepository = $this->getDoctrine()->getRepository(News::class)->setCategory('event');
        $events = $eventRepository->findAll();

        return $this->render('pages/evenements.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
            'events' => $events,
        ]);
    }

    /* ************************************************ *
     * event
     * ************************************************ */

    /**
     * @Route("/evenements/{slug<\d*\/?\d*\/?.+>}", name="evenements_show")
     */
    public function evenementPage(
        Request $request,
        string $slug
    ) {
        $eventRepository = $this->getDoctrine()->getRepository(News::class)->setCategory('event');
        $event = $eventRepository->findBySlug($slug);
        // todo: redirect to 404 page
        if (null === $event) {
            return $this->index($manager);
        }

        $nextPreviousEvents = $eventRepository->findNextPrevious($event->getId());

        $activePageBreadCrumb[$slug] = $event->getTitle();

        return $this->render('pages/event.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo(), $activePageBreadCrumb),
            'event' => $event,
            'nextPreviousEvents' => $nextPreviousEvents,
        ]);
    }
}
