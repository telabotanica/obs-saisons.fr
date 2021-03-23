<?php

namespace App\Service;

use App\Entity\Station;
use Doctrine\ORM\EntityManagerInterface;

class Search
{
    public const STATIONS_SEARCH_KEYS = [
        'name',
        'locality',
        'department',
        'displayName',
    ];
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function stationsSearch(string $searchTerm)
    {
        $foundStations = [];
        foreach (self::STATIONS_SEARCH_KEYS as $searchKey) {
            $foundStationsWithThisKey = $this->em->getRepository(Station::class)
                ->search($searchTerm, $searchKey);
            if (!empty($foundStationsWithThisKey)) {
                $foundStations[$searchKey] = $foundStationsWithThisKey;
            }
        }

        return $foundStations;
    }

    public function stationsSearchRawResults(string $searchTerm)
    {
        // flatten search results
        $foundStations = array_merge(...array_values($this->stationsSearch($searchTerm)));
        //de-duplicate stations array
        $stations = [];
        $ids = [];
        foreach ($foundStations as $station) {
            $id = $station->getId();

            if (!in_array($id, $ids)) {
                $ids[] = $id;
                $stations[] = $station;
            }
        }

        return $stations;
    }
}
