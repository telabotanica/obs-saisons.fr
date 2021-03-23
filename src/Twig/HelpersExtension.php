<?php

namespace App\Twig;

use App\Service\SlugGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class AppExtension.
 */
class HelpersExtension extends AbstractExtension
{
    /**
     * @return array|TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('slugify', [
                $this,
                'slugify',
            ]),
            new TwigFilter('removeDuplicates', [
                $this,
                'removeDuplicates',
            ]),
        ];
    }

    public function slugify(string $string): string
    {
        $slugGenerator = new SlugGenerator();

        return $slugGenerator->slugify($string);
    }

    public function removeDuplicates(array $array): array
    {
        $removeDuplicates = [];
        foreach ($array as $value) {
            if (!in_array($value, $removeDuplicates)) {
                $removeDuplicates[] = $value;
            }
        }

        return $removeDuplicates;
    }
}
