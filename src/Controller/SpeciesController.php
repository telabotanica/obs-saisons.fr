<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Species;
use App\Service\BreadcrumbsGenerator;
use Doctrine\ORM\EntityManagerInterface;
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
            'allSpecies' => $manager->getRepository(Species::class)->findAll(),
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs(),
        ]);
    }

    /**
     * @Route("/especes/{vernacularName}", name="species_single_show")
     */
    public function showSpecy(
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        string $vernacularName
    ) {
        $species = $manager->getRepository(Species::class)->findOneBy(['vernacular_name' => $vernacularName]);
        if (!$species) {
            throw $this->createNotFoundException('L’espèce n’a pas été trouvée');
        }

        $post = $species->getPost();
        if (!$post) {
            throw $this->createNotFoundException('La fiche espèce n’a pas été trouvée');
        }

        return $this->render('pages/species/species-single.html.twig', [
            'species' => $species,
            'post' => $post,
            'breadcrumbs' => $breadcrumbsGenerator->setActiveTrail($vernacularName)
                ->getBreadcrumbs(Post::CATEGORY_PARENT_ROUTE[$post->getCategory()]),
        ]);
    }
}
