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

    /**
     * @return array
     */
    public function getBreadcrumbs(string $currentUrl, array $activePageBreadCrumb = null)
    {
        // slices current url in $urlParts
        if (preg_match("/\d{4}\/\d{2}\/.+/", $currentUrl, $matches)) {
            $newsSLug = $matches[0];
            $currentUrl = str_replace($newsSLug, '', $currentUrl);
        }
        $urlParts = array_filter(explode('/', $currentUrl));
        if (isset($newsSLug)) {
            array_push($urlParts, $newsSLug);
        }

        // builds breadcrumbs array
        $breadcrumbs = [];
        if (empty($activePageBreadCrumb)) {
            $activePageBreadCrumb = [];
        }
        $pageBreadCrumbs = array_merge(self::MENU, self::OTHER_BREADCRUMBS, $activePageBreadCrumb);
        foreach ($urlParts as $urlPart) {
            if (!empty($urlPart)) {
                $breadcrumbs[$urlPart] = (isset($pageBreadCrumbs[$urlPart])) ? $pageBreadCrumbs[$urlPart] : $urlPart;
            }
        }

        return $breadcrumbs;
    }
}
