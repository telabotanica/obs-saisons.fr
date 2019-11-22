<?php
namespace App\Controller;

use App\Entity\News;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class NewsController
 * @package App\Controller
 */
class NewsController extends PagesController
{
    /* ************************************************ *
     * actualites
     * ************************************************ */
    /**
     * @param Request $request
     * @param ObjectManager $manager
     *
     * @Route("/actualites", name="actualites")
     */
    public function actualitesIndex (
        Request $request,
        ObjectManager $manager
    ) {
        $articleRepository = $manager->getRepository(News::class)->setCategory('article');
        $articles = $articleRepository->findAll();

        return $this->render('pages/actualites.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
            'articles' => $articles
        ]);
    }

    /* ************************************************ *
     * article
     * ************************************************ */
    /**
     * @param Request $request
     * @param ObjectManager $manager
     * @param string $slug
     *
     * @Route("/actualites/{slug<\d*\/\d*\/.+>}", name="actualites_show")
     */
    public function articlePage(
        Request $request,
        ObjectManager $manager,
        string $slug
    )
    {
        $articleRepository = $manager->getRepository(News::class)->setCategory('article');
        $article = $articleRepository->findBySlug($slug);
        // todo: redirect to 404 page
        if (null === $article) return $this->index($manager);

        $nextPreviousArticles = $articleRepository->findNextPrevious($article->getId());

        $activePageBreadCrumb[$slug] = $article->getTitle();

        return $this->render('pages/article.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo(), $activePageBreadCrumb),
            'article' => $article,
            'nextPreviousArticles' => $nextPreviousArticles
        ]);
    }

    /* ************************************************ *
     * evenements
     * ************************************************ */
    /**
     * @param ObjectManager $manager
     * @param Request $request
     *
     * @Route("/evenements", name="evenements")
     */
    public function evenementsIndex (
        Request $request,
        ObjectManager $manager
    ) {
        $eventRepository = $manager->getRepository(News::class)->setCategory('event');
        $events = $eventRepository->findAll();

        return $this->render('pages/evenements.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
            'events' => $events
        ]);
    }

    /* ************************************************ *
     * event
     * ************************************************ */
    /**
     * @param Request $request
     * @param ObjectManager $manager
     * @param string $slug
     *
     * @Route("/evenements/{slug<\d*\/?\d*\/?.+>}", name="evenements_show")
     */
    public function evenementPage(
        Request $request,
        ObjectManager $manager,
        string $slug
    )
    {
        $eventRepository = $manager->getRepository(News::class)->setCategory('event');
        $event = $eventRepository->findBySlug($slug);
        // todo: redirect to 404 page
        if (null === $event) return $this->index($manager);

        $nextPreviousEvents = $eventRepository->findNextPrevious($event->getId());

        $activePageBreadCrumb[$slug] = $event->getTitle();

        return $this->render('pages/event.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo(), $activePageBreadCrumb),
            'event' => $event,
            'nextPreviousEvents' => $nextPreviousEvents
        ]);
    }


}