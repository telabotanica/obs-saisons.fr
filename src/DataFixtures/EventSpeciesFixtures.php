<?php

namespace App\DataFixtures;

use App\Entity\EventSpecies;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker;

class EventSpeciesFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');
        $eventsSpeciesReferences = [];
        // combining plant-events with plants
        $eventsReferences = range(1, 7); // currently only event with reference 8 is for animals
        $speciesReferences = range(1, 55); // currently 18 animal spiecies (the 6 last)
        foreach ($eventsReferences as $eventsReference) {
            foreach ($speciesReferences as $speciesReference) {
                $eventsSpeciesReferences[] = [
                    'event' => $eventsReference,
                    'species' => $speciesReference,
                ];
            }
        }
        // combining animal-events with animals
        $speciesReferences = range(56, 73);
        foreach ($speciesReferences as $speciesReference) {
            array_push($eventsSpeciesReferences, [
                    'event' => 8,
                    'species' => $speciesReference,
                ]);
        }

        foreach ($eventsSpeciesReferences as $i => $eventsSpeciesReference) {
            $event = $this->getReference('event-'.$eventsSpeciesReference['event']);
            $species = $this->getReference('species-'.$eventsSpeciesReference['species']);
            $startDate = $faker->dateTimeBetween('0000-01-01', '0000-09-30', 'Europe/Paris');
            $endDate = $faker->dateTimeInInterval($startDate, '+ 3 months', 'Europe/Paris');

            $eventsSpecies = new EventSpecies($event, $species);
            $eventsSpecies->setDescription($faker->paragraph(3, true));
            $eventsSpecies->setPicture($faker->imageUrl(800, 600, 'nature'));
            $eventsSpecies->setStartDate($startDate);
            $eventsSpecies->setEndDate($endDate);

            $manager->persist($eventsSpecies);

            $this->addReference(sprintf('eventsSpecies-%d', $i), $eventsSpecies);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
            SpeciesFixtures::class,
            EventFixtures::class,
        ];
    }
}