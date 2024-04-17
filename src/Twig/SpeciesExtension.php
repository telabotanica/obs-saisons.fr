<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class AppExtension.
 */
class SpeciesExtension extends AbstractExtension
{
    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('setSpeciesDisplayData', [
                $this,
                'setSpeciesDisplayData',
            ]),
            new TwigFunction('speciesGroups', [
                $this,
                'speciesGroups',
            ]),
        ];
    }

    public function setSpeciesDisplayData(array $allSpecies): array
    {
        $speciesDisplayData = [];
        foreach ($allSpecies as $species) {
            $type = $species->getType();
            $i = array_search(
                $type,
                array_column($speciesDisplayData, 'type')
            );
            if (false === $i) {
                $speciesDisplayData[] = [
                    'type' => $type,
                    'typeSpecies' => [$species],
                ];
            } else {
                $speciesDisplayData[$i]['typeSpecies'][] = $species;
            }
        }

        return $speciesDisplayData;
    }

    public function speciesGroups(array $typeSpecies)
    {
        $groups = [];
        foreach ($typeSpecies as $species) {
            $vernacularNameParts = explode(' ', $species->getVernacularName());
            if ($vernacularNameParts[0] == 'Genêt'){
                $groupName = $species->getVernacularName();
            } else {
                $groupName = $vernacularNameParts[0];
            }

            if (empty($groups[$groupName])) {
                $groups[$groupName] = [];
            }
            $groups[$groupName][] = $species;
        }

        return $groups;
    }
}
