<?php

namespace App\Twig;

use App\Service\BreadcrumbsGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class AppExtension.
 */
class MenuExtension extends AbstractExtension
{
    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('getActiveMenuItem', [
                $this,
                'getActiveMenuItem',
            ]),
            new TwigFunction('getActiveSubMenuItem', [
                $this,
                'getActiveSubMenuItem',
            ])
        ];
    }

    public function getActiveMenuItem(array $breadcrumbs = [])
    {
        if (empty($breadcrumbs)) {
            return 'homepage';
        }

        $slugs = array_reverse(array_keys($breadcrumbs)); //look for the deepest match in path
        $menuSlugs = array_keys(BreadcrumbsGenerator::MENU);

        foreach ($slugs as $slug) {
            if (in_array($slug, $menuSlugs)) {
                return $slug;
            } elseif ('stations' === $slug) {
                return 'my_stations';
            }
        }

        return null;
    }

    public function getActiveSubMenuItem(array $breadcrumbs = [])
    {
        if (empty($breadcrumbs)) {
            return 'homepage';
        }

        $slugs = array_reverse(array_keys($breadcrumbs)); //look for the deepest match in path
        $menuSlugs = array_keys(BreadcrumbsGenerator::OTHER_BREADCRUMBS);

        foreach ($slugs as $slug) {
            if (in_array($slug, $menuSlugs)) {
                return $slug;
            }
        }

        return null;
    }

}
