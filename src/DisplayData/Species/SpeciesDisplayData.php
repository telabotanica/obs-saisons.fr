<?php

namespace App\DisplayData\Species;

use App\Entity\Species;
use App\Entity\TypeSpecies;
use Doctrine\Common\Persistence\ManagerRegistry;

class SpeciesDisplayData
{
    private $manager;
    private $allSpecies;
    private $types;

    public function __construct(ManagerRegistry $manager, array $allSpecies = null)
    {
        $this->manager = $manager;

        if (null === $allSpecies) {
            $this->allSpecies = [];
            self::setAllSpecies();
        } else {
            $this->allSpecies = $allSpecies;
        }

        $this->types = [];
        self::setTypes();
    }

    private function setAllSpecies(): self
    {
        $this->allSpecies = $this->manager->getRepository(Species::class)
            ->findAll()
        ;

        return $this;
    }

    // actual types list depends on actual registers in database species
    private function setTypes(): self
    {
        foreach ($this->allSpecies as $species) {
            $type = $species->getType();
            if (!in_array($type, $this->types)) {
                $this->types[] = $type;
            }
        }

        return $this;
    }

    public function getTypes()
    {
        return $this->types;
    }

    public function filterSpeciesByType(TypeSpecies $type): array
    {
        $speciesByType = [];
        foreach ($this->allSpecies as $species) {
            if ($species->getType() === $type) {
                $speciesByType[] = $species;
            }
        }

        return $speciesByType;
    }
}
