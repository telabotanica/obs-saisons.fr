<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Species;
use App\Entity\EventSpecies;
use App\Service\BreadcrumbsGenerator;
use App\Service\DateService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SpeciesController extends AbstractController
{
    /* ************************************************ *
     * Species
     * ************************************************ */

    /**
     * @Route("/especes", name="especes")
     */
    public function species(
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator
    ) {
        return $this->render('pages/species/species-list.html.twig', [
            'allSpecies' => $manager->getRepository(Species::class)->findAllOrderedByTypeAndVernacularName(),
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
        ]);
    }

    /**
     * @Route("/especes/{vernacularName}", name="species_single_show")
     */
    public function showSpecy(
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        string $vernacularName,
		DateService $dateService
    ) {
        $species = $manager->getRepository(Species::class)->findOneBy(['vernacular_name' => $vernacularName]);
        if (!$species) {
            throw $this->createNotFoundException('L’espèce n’a pas été trouvée');
        }

        $post = $species->getPost();
        if (!$post) {
            throw $this->createNotFoundException('La fiche espèce n’a pas été trouvée');
        }

        $speciesEvents = $manager->getRepository(EventSpecies::class)
            ->findBy(['species' => $species]);
        $validEvents = [];
        foreach ($speciesEvents as $eventSpecies) {
            $validEvents[] = $eventSpecies->getEvent();
        }
		
		$calendar = [];
		$type = $species->getType()->getReign();
		foreach ($speciesEvents as $stage){
			$calendar[] = $dateService->calculCalendrierPheno($stage);
		}

        // Fetch the species based on vernacular name
        $species = $manager->getRepository(Species::class)->findOneBy(['vernacular_name' => $vernacularName]);
        if (!$species) {
            throw $this->createNotFoundException('L’espèce n’a pas été trouvée');
        }

// Query to find the latest 10 validated images for the specified species
        $imagesQuery = $manager->createQueryBuilder()
            ->select(
                'partial o.{id, picture, is_picture_valid, updatedAt}',
                'partial u.{id, name}',
                'partial e.{id, name}',
                'partial i.{id}'
            )
            ->from('App\Entity\Observation', 'o')
            ->leftJoin('o.user', 'u')
            ->leftJoin('o.event', 'e')
            ->leftJoin('o.individual', 'i')
            ->where('o.is_picture_valid = :valid AND i.species = :species')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(10)
            ->setParameters([
                'valid' => 1,
                'species' => $species
            ]);

// Execute the query to get the results
        $images = $imagesQuery->getQuery()->getResult();

//        dd($images);

		
        return $this->render('pages/species/species-single.html.twig', [
            'images'=>$images,
            'species' => $species,
            'eventsSpecies' => $speciesEvents,
			'type' => $type,
            'post' => $post,
			'calendar' => $calendar,
            'breadcrumbs' => $breadcrumbsGenerator->setActiveTrail($vernacularName)
                ->getBreadcrumbs(Post::CATEGORY_PARENT_ROUTE[$post->getCategory()]),
        ]);
    }
}
