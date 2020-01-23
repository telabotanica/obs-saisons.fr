<?php

namespace App\Controller;

use App\DisplayData\Espece\SpeciesDisplayData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EspeceController extends PagesController
{
    /* ************************************************ *
     * Espece
     * ************************************************ */

    /**
     * @Route("/especes", name="especes")
     */
    public function especes(Request $request): Response
    {
        return $this->render('pages/especes.html.twig', [
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
                        'image' => $species->getPhoto(),
                        'heading' => [
                            'is_link' => true,
                            'title' => $species->getNomVernaculaire(),
                            'text' => $species->getNomScientifique(),
                        ],
                    ],
                ];
            }

            $accordions[] = [
                'tab_reference' => $type->getReigne(),
                'title' => $type->getNom(),
                'contents' => $contents,
            ];
        }

        return $accordions;
    }
}
