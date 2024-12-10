<?php

namespace App\Controller;

use App\Entity\Observation;
use App\Entity\Post;
use App\Entity\Species;
use App\Entity\Station;
use App\Entity\User;
use App\Entity\TypeSpecies;
use App\Entity\Event;
use App\Entity\EventSpecies;
use App\Form\StatsType;
use App\Entity\FrenchRegions;
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
            'title' => 'Questions fréquentes',
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
        EntityManagerInterface $em, Request $request) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'resultats']
        );
		$minYear = $em->getRepository(Observation::class)->findMinYear();
		$years = $em->getRepository(Observation::class)->findAllYears();
		
		$yearsIndexed = [];
		foreach ($years as $year){
			$yearsIndexed[$year] = $year;
		}
		
		$year = new \DateTime('now');
		$year = $year->format('Y');
	
		$form = $this->createForm(StatsType::class,$years, ['years'=>$yearsIndexed]);
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()){
			$year = $form->get('years')->getData();
		}
	
		$obsPerYear = $em->getRepository(Observation::class)->findObsCountPerYear($year);
		$nbStations = $em->getRepository(Station::class)->countStationsEachYear($year);
		$newMembers = $em->getRepository(User::class)->findNewMembersPerYear($year);
		$activeMembers= $em->getRepository(Observation::class)->findActiveMembersPerYear($year);

        return $this->render('pages/static/resultats.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'page' => $page,
			'years' => $years,
			'min_year' => $minYear,
			'obsPerYear' => $obsPerYear,
			'nbStations' => $nbStations,
			'newMembers' => $newMembers,
			'activeMembers' => $activeMembers,
			'form' => $form->createView()
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
     * @Route("/ods-occitanie", name="ods-occitanie")
     */
    public function odsOccitanie(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'ods-occitanie']
        );

        return $this->render('pages/static-page.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'title' => 'ODS Occitanie',
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

    /**
     * @Route("/calendrier", name="calendrier")
     */
    public function calendrier(
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em,
        Request $request
    ) {
        $typeSpecies = $em->getRepository(TypeSpecies::class)->findAll();
        
        $species = $em->getRepository(Species::class)->findAllActive();
        
        $minYear = $em->getRepository(Observation::class)->findMinYear();
        
        $events = $em->getRepository(Event::class)->findAll();
        
        $allEventSpecies = $em->getRepository(EventSpecies::class)->findAllScalarIds();
        
        // Prise en compte de l'id de l'espece sélectionner sur le dropdown
        $selectedSpeciesIds = $request->query->get('species', []);
        
        if (empty($selectedSpeciesIds)) {
            // Fetch 7 random species if none are selected
            $selectedSpeciesIds = [];
            $randomIndexes = array_rand($species, 2);
            foreach ((array)$randomIndexes as $index) {
                $selectedSpeciesIds[] = $species[$index]->getId();
            }
        } else {
            if (!is_array($selectedSpeciesIds)) {
                $selectedSpeciesIds = [$selectedSpeciesIds];
            }
        }
        
        // Prise en compte de l'id de l'event sélectionner sur le dropdown
        $selectedEventId = $request->query->get('event', []);
        
        // Prise en compte de l'année sélectionner sur le dropdown
        $selectedYear = $request->query->get('year', [1]);
        
        $observations = $em->getRepository(Observation::class)
        ->findObservationsGraph($selectedSpeciesIds, $selectedEventId, $selectedYear);
        
        // flatten array, eventsIds list indexed by species
        $speciesEvents = [];
        foreach ($allEventSpecies as $eventSpecies) {
            $speciesEvents[$eventSpecies['species_id']] = $eventSpecies['events_ids'];
        }
        
        $eventsIds = [];
        foreach ($events as $event) {
            $eventsIds[$event->getName()][] = $event->getId();
        }
        
        // text content
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'explorer-les-donnees']
            );
        
        $page = $em->getRepository(Post::class)->findOneBy(
            ['category' => Post::CATEGORY_PAGE, 'slug' => 'calendrier']
        );
//         if(empty($page)){
//             $page = new Post();
//             $page->setSlug('calendrier'); 
//         }
        return $this->render('pages/static/calendrier-saisons.html.twig', [
            'observations' => $observations,
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
            'allTypeSpecies' => $typeSpecies,
            'allSpecies' => $species,
            'minYear' => $minYear,
            'eventsIds' => $eventsIds,
            'events' => $events,
            'selectedYear' => $selectedYear,
            'speciesEvents' => $speciesEvents,
            'selectedSpeciesIds' => $selectedSpeciesIds,
            'selectedEventId' => $selectedEventId,
            'regions' => FrenchRegions::getRegionsList(),
            'departments' => FrenchRegions::getDepartmentsList(),
            'title' => 'Calendrier des saisons',
            'page' => $page,
        ]);
    }
}
