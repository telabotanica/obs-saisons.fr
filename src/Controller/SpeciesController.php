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
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
        ]);
    }
}
