<?php

namespace App\Controller;

use App\DisplayData\Species\SpeciesDisplayData;
use App\Entity\Post;
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
        return $this->render('pages/species/species.html.twig', [
            'accordions' => $this->setAccordions(),
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
        ]);
    }

    private function setAccordions(): array
    {
        $accordions = [];
        $manager = $this->getDoctrine();
        $speciesDisplayData = new SpeciesDisplayData($manager);
        $types = $speciesDisplayData->getTypes();

        foreach ($types as $type) {
            $allSpecies = $speciesDisplayData->filterSpeciesByType($type);
            $contents = [];
            foreach ($allSpecies as $species) {
                $contents[] = [
                    'type' => 'include',
                    'include_uri' => 'components/list-cards.html.twig',
                    'include_object_name' => 'list_card',
                    'data' => [
                        'image' => '/media/species/'.$species->getPicture().'.jpg',
                        'heading' => [
                            'is_link' => true,
                            'href' => $this->generateUrl('specy_show', ['vernacularName' => $species->getVernacularName()]),
                            'title' => $species->getVernacularName(),
                            'text' => $species->getScientificName(),
                        ],
                    ],
                ];
            }

            $accordions[] = [
                'tab_reference' => $type->getReign(),
                'title' => $type->getName(),
                'contents' => $contents,
            ];
        }

        return $accordions;
    }

    /**
     * @Route("/espece/{vernacularName}", name="specy_show")
     */
    public function showSpecy(string $vernacularName, EntityManagerInterface $manager): Response
    {
        $specy = $manager->getRepository(Species::class)->findOneBy(['vernacular_name' => $vernacularName]);
        if (!$specy) {
            $this->createNotFoundException('L’espèce n’a pas été trouvée');
        }

        $post = $specy->getPost();
        if (!$post) {
            $this->createNotFoundException('La fiche espèce n’a pas été trouvée');
        }

        return $this->render('pages/species/specy.html.twig', [
            'specy' => $specy,
            'post' => $post,
        ]);
    }
}
