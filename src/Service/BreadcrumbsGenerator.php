<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class BreadcrumbsGenerator.
 */
class BreadcrumbsGenerator
{
    const MENU = [
        'a-propos' => 'À propos',
        'news_posts_list' => 'Actualités',
        'event_posts_list' => 'Évènements',
        'participer' => 'Comment participer',
        'especes' => 'Espèces à observer',
        'my_stations' => 'Saisir mes données',
        'resultats' => 'Résultats',
        'outils-ressources' => 'Outils & ressources',
        'relais' => 'Relais et déclinaisons',
    ];
    const OTHER_BREADCRUMBS = [
        'stations' => 'Stations d’observation',
        'stations_search' => 'Recherche de stations',
        'my_stations' => 'Mes stations',
        'user_dashboard' => 'Tableau de bord',
        'user_profile' => 'Profil de l’utilisateur',
        'aide' => 'Aide',
        'faq' => 'Questions fréquentes',
        'glossaire' => 'Glossaire',
        'resultats-scientifiques' => 'Résultats scientifiques',
        'lettres-de-printemps' => 'Lettres de printemps',
        'explorer-les-donnees' => 'Explorer et visualiser les données',
        'outils' => 'Outils',
        'ressources-pedagogiques' => 'Ressources pédagogiques',
        'transmettre' => 'Transmettre',
        'devenir-relais' => 'Devenir relais',
        'se-former' => 'Se former',
        'les-relais-ods' => 'Les relais de l\'ODS',
        'ods-provence' => 'ODS Provence',
		'ods-occitanie'=> 'ODS Occitanie',
        'mentions-legales' => 'Mentions légales',
        'expositions' => 'Expositions',
    ];
    const EDITABLE_PAGES = [
        'a-propos',
        'aide',
        'faq',
        'glossaire',
        'participer',
        'resultats',
        'resultats-scientifiques',
        'lettres-de-printemps',
        'explorer-les-donnees',
        'outils-ressources',
        'outils',
        'ressources-pedagogiques',
        'transmettre',
        'relais',
        'devenir-relais',
        'se-former',
        'les-relais-ods',
        'ods-provence',
        'ods-occitanie',
        'mentions-legales',
        'expositions',
    ];
	
	const SUBMENU = [
		'news_posts_list' => [
			'news_post_create'=> 'Saisir une actualité',
		],
		'event_posts_list' => [
			'event_post_create'=> 'Créer un évènement',
		],
		'resultats' => [
			'explorer-les-donnees' => 'Explorer et visualiser les données',
			'lettres-de-printemps' => 'Lettres de printemps',
//			'resultats-scientifiques' => 'Résultats scientifiques',
			'export' => 'Export des données',
		],
		'outils-ressources' => [
			'outils' => 'Outils',
			'ressources-pedagogiques'=> 'Ressources pédagogiques',
			'expositions'=> 'Expositions',
			'transmettre'=> 'Transmettre',
		],
		'relais' => [
			'ods-provence'=> 'ODS Provence',
			'ods-occitanie'=> 'ODS Occitanie',
		],
	];

    protected RequestStack $requestStack;
    private array $trails;
    private array $activeTrail;
    private string $removeStringFromPath;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
        $this->trails = [];
        $this->activeTrail = [];
        $this->removeStringFromPath = '';
    }

    public function setToRemoveFromPath(string $string)
    {
        $this->removeStringFromPath = $string;

        return $this;
    }

    private function buildTrail(string $route, string $label)
    {
        return [
            'route' => urldecode($route),
            'label' => $label,
        ];
    }

    public function addTrail(string $route, string $label)
    {
        $this->trails[] = $this->buildTrail($route, $label);
    }

    public function getTrails(): array
    {
        $breadcrumbs = [];
        foreach ($this->trails as $trail) {
            $breadcrumbs[$trail['route']] = $trail['label'];
        }

        return $breadcrumbs;
    }

    public function buildBreadcrumbs(array $routes): array
    {
        $pageBreadcrumbs = array_merge(self::MENU, self::OTHER_BREADCRUMBS);
        foreach ($routes as $route) {
			// Breadcrumb s'il y a des sous menu
			foreach (self::SUBMENU as $key => $value){
				if (in_array($route, array_keys($value))){
					$this->addTrail($key, $pageBreadcrumbs[$key] ?? $key);
					$this->addTrail($route, $value[$route] ?? $value[$route]);
				}
			}
            $this->addTrail($route, $pageBreadcrumbs[$route] ?? $route);
        }
		
        if (!empty($this->activeTrail)) {
            $this->addTrail($this->activeTrail['route'], $this->activeTrail['label']);
        }

        return $this->getTrails();
    }

    private function getRoutes(string $urlInfos): array
    {
        return array_filter(explode('/', $urlInfos));
    }

    public function setActiveTrail(string $route = null, string $label = null): self
    {
        if (!$route) {
            $route = $this->requestStack->getCurrentRequest()
                ->attributes->get('_route');
        }

        $label = $label ?? array_merge(BreadcrumbsGenerator::MENU, BreadcrumbsGenerator::OTHER_BREADCRUMBS)[$route] ?? $route;

        $this->activeTrail = $this->buildTrail($route, $label);

        return $this;
    }

    /**
     * @return array
     */
    public function getBreadcrumbs(string $urlInfos = null)
    {
        $request = $this->requestStack->getCurrentRequest();

        $pathInfo = $request->getPathInfo();
        if (!empty($this->removeStringFromPath)) {
            $pathInfo = str_replace($this->removeStringFromPath, '', $pathInfo);
        }
        if (!$urlInfos) {
            if (1 === count($this->getRoutes($pathInfo))) {
                return $this->buildBreadcrumbs(
                    [$request->attributes->get('_route')]
                );
            }

            $urlInfos = $pathInfo;
        }

        // remove slug part of the called page url (bc slug contains slash)
        if (!empty($this->activeTrail)) {
            $urlInfos = str_replace($this->activeTrail['route'], '', $urlInfos);
        }

        return $this->buildBreadcrumbs($this->getRoutes($urlInfos));
    }
}
