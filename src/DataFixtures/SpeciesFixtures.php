<?php

namespace App\DataFixtures;

use App\Entity\Species;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker;

class SpeciesFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');
        $allSpeciesData = json_decode(file_get_contents('src/Ressources/ods_species.json'));
        foreach ($allSpeciesData as $i => $speciesData) {
            $species = new Species();
            $species->setVernacularName($speciesData->vernacular_name);
            $species->setScientificName($speciesData->scientific_name);
            $species->setDescription($speciesData->description);
            $species->setType($this->getReference(sprintf('typesSpecies-%d', $speciesData->type_id)));
            $species->setIsActive($speciesData->is_active);
            $species->setPicture($faker->imageUrl(800, 600, 'nature'));


            $manager->persist($species);

            $this->addReference(sprintf('species-%d', $i + 1), $species);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [
            TypeSpeciesFixtures::class,
        ];
    }
}
