<?php

namespace App\Service;

use App\Entity\Station;
use Doctrine\ORM\EntityManagerInterface;

class Search
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function stationsSearch(string $searchValue)
    {
        $foundStations = [];
        $tansSearchKeys = [
            'name' => 'Nom de station',
            'locality' => 'Commune',
            'department' => 'Numéro de département',
            'displayName' => 'Nom du créateur',
        ];
        foreach (array_keys($tansSearchKeys) as $searchKey) {
            $foundStationsWithThisKey = $this->em->getRepository(Station::class)
                ->search($searchValue, $searchKey);
            if (!empty($foundStationsWithThisKey)) {
                $foundStations[$tansSearchKeys[$searchKey]] = $foundStationsWithThisKey;
            }
        }

        return $foundStations;
    }
}
