<?php

namespace App\Controller;

use App\Entity\Species;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SpeciesController extends PagesController
{
    /* ************************************************ *
     * Species
     * ************************************************ */

    /**
     * @Route("/especes", name="especes")
     */
    public function species(Request $request): Response
    {
//        return $this->render('pages/species/list.html.twig', [
//            'accordions' => $this->setAccordions(),
        return $this->render('pages/species/species-list.html.twig', [
            'allSpecies' => $this->getDoctrine()->getRepository(Species::class)->findAll(),
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }

    /**
     * @Route("/espece/{vernacularName}", name="species_single_show")
     */
    public function showSpecy(string $vernacularName, EntityManagerInterface $manager): Response
    {
        $species = $manager->getRepository(Species::class)->findOneBy(['vernacular_name' => $vernacularName]);
        if (!$species) {
            $this->createNotFoundException('L’espèce n’a pas été trouvée');
        }

        $post = $species->getPost();
        if (!$post) {
            $this->createNotFoundException('La fiche espèce n’a pas été trouvée');
        }

        return $this->render('pages/species/species-single.html.twig', [
            'species' => $species,
            'post' => $post,
        ]);
    }
}
