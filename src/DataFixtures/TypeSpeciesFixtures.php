<?php

namespace App\DataFixtures;

use App\Entity\TypeSpecies;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class TypeSpeciesFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $typesSpeciesData = json_decode(file_get_contents('src/Ressources/ods_types_species.json'));

        foreach ($typesSpeciesData as $i => $typeSpeciesData) {
            $typeSpecies = new TypeSpecies();
            $typeSpecies->setName($typeSpeciesData->name);
            $typeSpecies->setReign($typeSpeciesData->reign);

            $manager->persist($typeSpecies);

            // there are 7 typeSpecies (const)
            $this->addReference(sprintf('typesSpecies-%d', $i + 1), $typeSpecies);
        }

        $manager->flush();
    }
}
