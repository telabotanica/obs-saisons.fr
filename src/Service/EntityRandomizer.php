<?php

namespace App\Service;

use App\Entity\Species;

class EntityRandomizer
{
    public function getRandomSpecies($species, $number)
    {
        $randomIndexes = array_rand($species, $number);
        $selectedSpeciesIds = [];
        foreach ((array)$randomIndexes as $index) {
            $selectedSpeciesIds[] = $species[$index]->getId();
        }
        return $selectedSpeciesIds;
    }
}