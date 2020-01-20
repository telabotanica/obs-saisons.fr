<?php

namespace App\Service;

/**
 * Class BreadcrumbsGenerator.
 */
class BreadcrumbsGenerator
{
    const MENU = [
        'apropos' => 'À propos',
        'actualites' => 'Actualités',
        'evenements' => 'Évènements',
        'especes' => 'Espèces à Observer',
        'participer' => 'Participer',
        'resultats' => 'Résultats',
        'outils-ressources' => 'Outils & ressources',
        'relais' => 'Relais',
    ];
    const OTHER_BREADCRUMBS = [
        'stations' => 'Stations d\'observation',
        'station-page' => 'Page de la station',
    ];

    private $trails;

    public function __construct()
    {
        $this->trails = [];
    }

    public function addTrail(string $label, string $route)
    {
        $this->trails[] = [
            'label' => $label,
            'route' => $route,
        ];
    }

    public function getTrails(): array
    {
        $breadcrumbs = [];
        foreach ($this->trails as $trail) {
            $breadcrumbs[$trail['route']] = $trail['label'];
        }

        return $breadcrumbs;
    }

    /**
     * @return array
     */
    public function getBreadcrumbs(string $currentUrl, array $activePageBreadCrumb = [])
    {
        // remove slug part of the called page url (bc slug contains slash)
        if (!empty($activePageBreadCrumb)) {
            $currentUrl = str_replace($activePageBreadCrumb['slug'], '', $currentUrl);
        }
        // get routes
        $urlParts = array_filter(explode('/', $currentUrl));

        // builds breadcrumbs
        $pageBreadCrumbs = array_merge(self::MENU, self::OTHER_BREADCRUMBS);
        foreach ($urlParts as $urlPart) {
            $this->addTrail($pageBreadCrumbs[$urlPart] ?? $urlPart, $urlPart);
        }

        if (!empty($activePageBreadCrumb)) {
            $this->addTrail($activePageBreadCrumb['title'], $activePageBreadCrumb['slug']);
        }

        return $this->getTrails();
    }
}
