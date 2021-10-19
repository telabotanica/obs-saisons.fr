<?php

namespace App\Controller;

use App\Entity\Observation;
use App\Entity\Post;
use App\Service\BreadcrumbsGenerator;
use App\Service\FeaturedSpecies;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            'lastNewsPosts' => $postRepository->setCategory(Post::CATEGORY_NEWS)->findLastFeaturedPosts(),
            'lastEventPosts' => $postRepository->setCategory(Post::CATEGORY_EVENT)->findLastFeaturedPosts(),
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
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'a-propos']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'À propos de l’Observatoire des Saisons',
            'subtitle' => 'L’Observatoire des Saisons, présentation du programme et de l’équipe, historique et financeurs',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/participer", name="participer")
     */
    public function participer(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'participer']
        );

        return $this->render('pages/static/participer.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'page' => $page,
        ]);
    }

    /**
     * @Route("/outils-ressources", name="outils-ressources")
     */
    public function outilsRessources(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'outils-ressources']
        );

        return $this->render('pages/static/outils-ressources.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'page' => $page,
        ]);
    }

    /**
     * @Route("/relais", name="relais")
     */
    public function relais(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'relais']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Contactez les relais ODS de votre région',
            'subtitle' => 'Tous les relais du programme <strong>Observatoire des Saisons</strong> près de chez vous.',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/aide", name="aide")
     */
    public function aide(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'aide']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Aide',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/faq", name="faq")
     */
    public function faq(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'faq']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Questions fréquences',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/glossaire", name="glossaire")
     */
    public function glossaire(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'glossaire']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Glossaire',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/resultats-scientifiques", name="resultats-scientifiques")
     */
    public function resultatsScientifiques(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'resultats-scientifiques']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Résultats scientifiques',
            'subtitle' => 'Les avancées scientifiques grâce à toutes vos observations !',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/lettres-de-printemps", name="lettres-de-printemps")
     */
    public function lettresDePrintemps(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'lettres-de-printemps']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Lettres de printemps',
            'subtitle' => 'Chaque année, nous faisons le bilan ! Découvrez les bilans annuels de l’Observatoire des Saisons : les lettres de printemps.',
            'page' => $page,
        ]);
    }


    /**
     * @Route("/resultats", name="resultats")
     */
    public function explorerLesDonnees(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'resultats']
        );

        return $this->render('pages/static/resultats.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'page' => $page,
        ]);
    }

    /**
     * @Route("/outils", name="outils")
     */
    public function outils(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'outils']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Outils',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/ressources-pedagogiques", name="ressources-pedagogiques")
     */
    public function ressourcesPedagogiques(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'ressources-pedagogiques']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Ressources pédagogiques',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/transmettre", name="transmettre")
     */
    public function transmettre(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'transmettre']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Transmettre',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/devenir-relais", name="devenir-relais")
     */
    public function devenirRelais(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'devenir-relais']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Devenir relais de l’Observatoire des Saisons',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/se-former", name="se-former")
     */
    public function seFormer(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'se-former']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Se former',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/les-relais-ods", name="les-relais-ods")
     */
    public function lesRelaisOds(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'les-relais-ods']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Les relais de l’Observatoire des Saisons',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/ods-provence", name="ods-provence")
     */
    public function odsProvence(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'ods-provence']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'ODS Provence',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/mentions-legales", name="mentions-legales")
     */
    public function mentionsLegales(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'mentions-legales']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Mentions légales',
            'page' => $page,
        ]);
    }

    /**
     * @Route("/expositions", name="expositions")
     */
    public function expositions(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'expositions']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'Expositions',
            'page' => $page,
        ]);
    }
}
