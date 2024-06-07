<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventSpecies;
use App\Entity\FrenchRegions;
use App\Entity\Observation;
use App\Entity\Post;
use App\Entity\Species;
use App\Entity\TypeSpecies;
use App\Service\BreadcrumbsGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ResultsController extends AbstractController
{
    /**
     * @Route("/explorer-les-donnees", name="explorer-les-donnees")
     */
    public function results(
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
            $randomIndexes = array_rand($species, 7);
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
        $selectedYear = $request->query->get('year', []);

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

//        dd($observations);

        return $this->render('pages/resultats-carte-calendriers.html.twig', [
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
            'page' => $page,
        ]);
    }

}
