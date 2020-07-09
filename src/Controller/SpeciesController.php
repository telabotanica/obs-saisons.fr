<?php

namespace App\Controller;

use App\Entity\Species;
use App\Service\BreadcrumbsGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        Request $request,
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator
    ) {
        return $this->render('pages/species/species-list.html.twig', [
            'allSpecies' => $manager->getRepository(Species::class)->findAll(),
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }

    /**
     * @Route("/espece/{vernacularName}", name="species_single_show")
     */
    public function showSpecy(
        Request $request,
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        string $vernacularName
    ) {
        $species = $manager->getRepository(Species::class)->findOneBy(['vernacular_name' => $vernacularName]);
        if (!$species) {
            $this->createNotFoundException('L’espèce n’a pas été trouvée');
        }

        $post = $species->getPost();
        if (!$post) {
            $this->createNotFoundException('La fiche espèce n’a pas été trouvée');
        }

        $activePageBreadCrumb = [
            'slug' => $vernacularName,
            'title' => $vernacularName,
        ];

        return $this->render('pages/species/species-single.html.twig', [
            'species' => $species,
            'post' => $post,
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo(), $activePageBreadCrumb),
        ]);
    }
}
