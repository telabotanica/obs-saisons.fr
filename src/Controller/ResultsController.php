<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventSpecies;
use App\Entity\FrenchRegions;
use App\Entity\Observation;
use App\Entity\Species;
use App\Entity\TypeSpecies;
use App\Service\BreadcrumbsGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ResultsController extends AbstractController
{
    /**
     * @Route("/resultats", name="resultats")
     */
    public function results(
        Request $request,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        EntityManagerInterface $em
    ) {
        $typeSpecies = $em->getRepository(TypeSpecies::class)->findAll();

        $species = $em->getRepository(Species::class)->findAllActive();

        $minYear = $em->getRepository(Observation::class)->findMinYear();

        $events = $em->getRepository(Event::class)->findAll();

        $allEventSpecies = $em->getRepository(EventSpecies::class)->findAllScalarIds();

        // flatten array, eventsIds list indexed by species
        $speciesEvents = [];
        foreach ($allEventSpecies as $eventSpecies) {
            $speciesEvents[$eventSpecies['species_id']] = $eventSpecies['events_ids'];
        }

        $eventsIds = [];
        foreach ($events as $event) {
            $eventsIds[$event->getName()][] = $event->getId();
        }

        return $this->render('pages/resultats.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'allTypeSpecies' => $typeSpecies,
            'allSpecies' => $species,
            'minYear' => $minYear,
            'eventsIds' => $eventsIds,
            'events' => $events,
            'speciesEvents' => $speciesEvents,
            'regions' => FrenchRegions::getRegionsList(),
            'departments' => FrenchRegions::getDepartmentsList(),
        ]);
    }
}
