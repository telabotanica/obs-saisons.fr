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
        'stations' => 'Stations d\'observation',
        'station-page' => 'Page de la station',
    ];

	public function index(Request $request)
	{
		return $this->render('pages/accueil.html.twig');
	}

    public function defaultPageRenderer(Request $request)
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

