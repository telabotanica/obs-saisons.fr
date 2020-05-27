<?php

namespace App\Controller;

use App\Entity\Species;
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
            'allSpecies' => $this->getDoctrine()->getRepository(Species::class)->findAll(),
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
        ]);
    }
}
