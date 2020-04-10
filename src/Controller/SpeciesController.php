<?php

namespace App\Controller;

use App\DisplayData\Species\SpeciesDisplayData;
use App\Entity\Species;
use App\Entity\TypeSpecies;
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
        return $this->render('pages/species.html.twig', [
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
}
