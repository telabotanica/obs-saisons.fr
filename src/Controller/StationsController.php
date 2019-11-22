<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class StationsController.
 */
class StationsController extends PagesController
{
    /* ************************************************ *
     * Stations
     * ************************************************ */

    /**
     * @Route("/participer/stations", name="stations")
     */
    public function stations(Request $request)
    {
        return $this->render('pages/stations.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
        ]);
    }

    /* ************************************************ *
     * Station
     * ************************************************ */

    /**
     * @Route("/participer/stations/station-page", name="station-page")
     */
    public function stationExp(Request $request)
    {
        return $this->render('pages/station-page.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
        ]);
    }

    /**
     * @Route("/participer/stations/{slug}", name="station-page_{slug}")
     */
    public function stationPage(Request $request)
    {
        return $this->render('pages/station-page.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route'),
        ]);
    }
}
