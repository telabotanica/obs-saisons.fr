<?php
namespace App\Controller;

use App\Entity\Sations;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class StationsController
 * @package App\Controller
 */
class StationsController extends PagesController
{
    /* ************************************************ *
     * Stations
     * ************************************************ */
    /**
     * @param Request $request
     *
     * @Route("/participer/stations", name="stations")
     */
    public function stations(Request $request)
    {
        return $this->render('pages/stations.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route')
        ]);
    }

    /* ************************************************ *
     * Station
     * ************************************************ */

    /**
     * @param Request $request
     *
     * @Route("/participer/stations/station-page", name="station-page")
     */
    public function stationExp(Request $request)
    {
        return $this->render('pages/station-page.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route')
        ]);
    }

    /**
     * @param Request $request
     *
     * @Route("/participer/stations/{slug}", name="station-page_{slug}")
     */
    public function stationPage(Request $request)
    {
        return $this->render('pages/station-page.html.twig', [
            'breadcrumbs' => $this->breadcrumbsGenerator->getBreadcrumbs($request->getPathInfo()),
            'route' => $request->get('_route')
        ]);
    }

}