<?php
// src/Controller/PagesController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * PagesController.
 */
class PagesController extends AbstractController
{
    const MENU = [
        'apropos' => 'À propos',
        'actualites' => 'Actualités',
        'evenements' => 'Évènements',
        'especes' => 'Espèces à Observer',
        'participer' => 'Participer',
        'resultats' => 'Résultats',
        'outils-ressources' => 'Outils & ressources',
        'relais' => 'Relais'
    ];
    const OTHER_BREADCRUMBS = [
        'actu' => 'Une actu',
        'evenement' => 'Un évènement',
        'station-obs' => 'Stations d\'observation',
        'station-page' => 'Page de la station',
    ];

	/**
	 * Index action.
	 *
	 * @param Request $request
	 *
	 * @Route("/", name="accueil")
	 */
	public function index(Request $request)
	{
		return $this->render('pages/accueil.html.twig');
	}

    /**
     * @param Request $request
     *
     * @Route("/apropos", name="apropos")
     */
    public function aproposPage(Request $request)
    {
        return $this->basicPageRenderer($request);
    }


    /**
     * @param Request $request
     *
     * @Route("/actualites", name="actualites")
     */
    public function actualitesPage(Request $request)
    {
        return $this->basicPageRenderer($request);
    }

    /**
     * @param Request $request
     *
     * @Route("/actualites/actu", name="actu")
     */
    public function actuPage(Request $request)
    {
        return $this->basicPageRenderer($request);
    }

    /**
     * @param Request $request
     *
     * @Route("/evenements", name="evenements")
     */
    public function evenementsPage(Request $request)
    {
        return $this->basicPageRenderer($request);
    }

    /**
     * @param Request $request
     *
     * @Route("/evenements/evenement", name="evenement")
     */
    public function evenementPage(Request $request)
    {
        return $this->basicPageRenderer($request);
    }

    /**
     * @param Request $request
     *
     * @Route("/especes", name="especes")
     */
    public function especesPage(Request $request)
    {
        return $this->basicPageRenderer($request);
    }

    /**
     * @param Request $request
     *
     * @Route("/participer", name="participer")
     */
	public function participer(Request $request)
	{
        return $this->basicPageRenderer($request);
	}

    /**
	 * @param Request $request
	 *
	 * @Route("/participer/station-obs", name="station-obs")
	 */
	public function stationObs(Request $request)
	{
        return $this->basicPageRenderer($request);
	}

    /**
	 * @param Request $request
	 *
	 * @Route("/participer/station-obs/station-page", name="station-page")
	 */
	public function stationPage(Request $request)
	{
        return $this->basicPageRenderer($request);
	}

    /**
     * @param Request $request
     *
     * @Route("/resultats", name="resultats")
     */
    public function resultatsPage(Request $request)
    {
        return $this->basicPageRenderer($request);
    }

    /**
     * @param Request $request
     *
     * @Route("/outils-ressources", name="outils-ressources")
     */
    public function outilsRessourcesPage(Request $request)
    {
        return $this->basicPageRenderer($request);
    }

    /**
     * @param Request $request
     *
     * @Route("/relais", name="relais")
     */
    public function relaisPage(Request $request)
    {
        return $this->basicPageRenderer($request);
    }

    /*
     * @param Request $request
     *
     * @return $bc
     *
     */
    public function basicPageRenderer(Request $request)
    {
        //breadcrumbs
        $currentRoute = $request->attributes->get('_route');
        $currentUrl = $this->get('router')->generate($currentRoute, array(), true);
        $urlParts = explode('/' , $currentUrl );
        array_shift($urlParts);
        $bc = array();
        foreach( $urlParts as $urlPart ) {
            if(isset(self::MENU[$urlPart])) {
                $bc[$urlPart] = self::MENU[$urlPart];
            } elseif(isset(self::OTHER_BREADCRUMBS[$urlPart])) {
                $bc[$urlPart] = self::OTHER_BREADCRUMBS[$urlPart];
            } elseif(!empty($urlPart)) {
                $bc[$urlPart] = $urlPart;
            } else {
                $bc = array();
            }
        }

        //render page
        return $this->render('pages/'.$currentRoute.'.html.twig', [
            'breadcrumbs' => $bc,
            'route' => $currentRoute
        ]);
    }


}

