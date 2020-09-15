<?php

namespace App\Entity;

class FrenchRegions
{
    public const ALL_DETAILS = [
        1 => [
            'name' => 'Auvergne-Rhône-Alpes',
            'departments' => [
                '01' => 'Ain',
                '03' => 'Allier',
                '07' => 'Ardèche',
                '15' => 'Cantal',
                '26' => 'Drôme',
                '38' => 'Isère',
                '42' => 'Loire',
                '43' => 'Haute-Loire',
                '63' => 'Puy-de-Dôme',
                '69' => 'Rhône',
//                '69D' => 'Rhône',
//                '69M' => 'Métropole de Lyon',
                '73' => 'Savoie',
                '74' => 'Haute-Savoie',
            ],
        ],
        2 => [
            'name' => 'Bourgogne-Franche-Comté',
            'departments' => [
                '21' => 'Côte-d\'Or',
                '25' => 'Doubs',
                '39' => 'Jura',
                '58' => 'Nièvre',
                '70' => 'Haute-Saône',
                '71' => 'Saône-et-Loire',
                '89' => 'Yonne',
                '90' => 'Territoire-de-Belfort',
            ],
        ],
        3 => [
            'name' => 'Bretagne',
            'departments' => [
                '22' => 'Côtes-d\'Armor',
                '29' => 'Finistère',
                '35' => 'Ille-et-Vilaine',
                '56' => 'Morbihan',
            ],
        ],
        4 => [
            'name' => 'Centre-Val de Loire',
            'departments' => [
                '18' => 'Cher',
                '28' => 'Eure-et-Loir',
                '36' => 'Indre',
                '37' => 'Indre-et-Loire',
                '41' => 'Loir-et-Cher',
                '45' => 'Loiret',
            ],
        ],
        5 => [
            'name' => 'Corse',
            'departments' => [
                '2A' => 'Corse-du-Sud',
                '2B' => 'Haute-Corse',
            ],
        ],
        6 => [
            'name' => 'Grand Est',
            'departments' => [
                '08' => 'Ardennes',
                '10' => 'Aube',
                '51' => 'Marne',
                '52' => 'Haute-Marne',
                '54' => 'Meurthe-et-Moselle',
                '55' => 'Meuse',
                '57' => 'Moselle',
                '67' => 'Bas-Rhin',
                '68' => 'Haut-Rhin',
                '88' => 'Vosges',
            ],
        ],
        7 => [
            'name' => 'Hauts-de-France',
            'departments' => [
                '02' => 'Aisne',
                '59' => 'Nord',
                '60' => 'Oise',
                '62' => 'Pas-de-Calais',
                '80' => 'Somme',
            ],
        ],
        8 => [
            'name' => 'Île-de-France',
            'departments' => [
                '75' => 'Paris',
                '77' => 'Seine-et-Marne',
                '78' => 'Yvelines',
                '91' => 'Essonne',
                '92' => 'Hauts-de-Seine',
                '93' => 'Seine-Saint-Denis',
                '94' => 'Val-de-Marne',
                '95' => 'Val-d\'Oise',
            ],
        ],
        9 => [
            'name' => 'Pays de la Loire',
            'departments' => [
                '44' => 'Loire-Atlantique',
                '49' => 'Maine-et-Loire',
                '53' => 'Mayenne',
                '72' => 'Sarthe',
                '85' => 'Vendée',
            ],
        ],
        10 => [
            'name' => 'Nouvelle-Aquitaine',
            'departments' => [
                '16' => 'Charente',
                '17' => 'Charente-Maritime',
                '19' => 'Corrèze',
                '23' => 'Creuse',
                '24' => 'Dordogne',
                '33' => 'Gironde',
                '40' => 'Landes',
                '47' => 'Lot-et-Garonne',
                '64' => 'Pyrénées-Atlantiques',
                '79' => 'Deux-Sèvres',
                '86' => 'Vienne',
                '87' => 'Haute-Vienne',
            ],
        ],
        11 => [
            'name' => 'Normandie',
            'departments' => [
                '14' => 'Calvados',
                '27' => 'Eure',
                '50' => 'Manche',
                '61' => 'Orne',
                '76' => 'Seine-Maritime',
            ],
        ],
        12 => [
            'name' => 'Occitanie',
            'departments' => [
                '09' => 'Ariège',
                '11' => 'Aude',
                '12' => 'Aveyron',
                '30' => 'Gard',
                '31' => 'Haute-Garonne',
                '32' => 'Gers',
                '34' => 'Hérault',
                '46' => 'Lot',
                '48' => 'Lozère',
                '65' => 'Hautes-Pyrénées',
                '66' => 'Pyrénées-Orientales',
                '81' => 'Tarn',
                '82' => 'Tarn-et-Garonne',
            ],
        ],
        13 => [
            'name' => 'Provence-Alpes-Côte d\'Azur',
            'departments' => [
                '04' => 'Alpes-de-Haute-Provence',
                '05' => 'Hautes-Alpes',
                '06' => 'Alpes-Maritimes',
                '13' => 'Bouches-du-Rhône',
                '83' => 'Var',
                '84' => 'Vaucluse',
            ],
        ],
    ];

    public static function getDepartmentsByRegionId(int $regionId): array
    {
        if (!array_key_exists($regionId, self::ALL_DETAILS)) {
            throw new \InvalidArgumentException(sprintf('Region with id %d is not supported', $regionId));
        }

        return self::ALL_DETAILS[$regionId]['departments'];
    }

    public static function getDepartmentsIdsByRegionId(int $regionId): array
    {
        return array_keys(self::getDepartmentsByRegionId($regionId));
    }

    public static function getRegionsList(): array
    {
        $regions = [];
        foreach (self::ALL_DETAILS as $id => $details) {
            $regions[$id] = $details['name'];
        }

        return $regions;
    }

    public static function getDepartmentsList(): array
    {
        $departments = [];
        foreach (self::ALL_DETAILS as $details) {
            foreach ($details['departments'] as $id => $department) {
                $departments[$id] = $department;
            }
        }
        ksort($departments);

        return $departments;
    }
}
