<?php

namespace App\DataFixtures;

use App\Entity\Individual;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker;

class IndividualFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 0; $i < 76; ++$i) {
            $individual = new Individual();
            $individual->setName($faker->sentence(6, true));
            $individual->setSpecies($this->getReference('species-'.$faker->numberBetween(1, 55)));
            $individual->setUser($this->getReference('user-'.$faker->randomDigit));
            $individual->setStation($this->getReference('station-'.$faker->randomDigit));

            $manager->persist($individual);

            $this->addReference(sprintf('individual-%d', $i), $individual);
        }

        for ($c = 76; $c < 100; ++$c) {
            $individual = new Individual();
            $individual->setName($faker->sentence(6, true));
            $individual->setSpecies($this->getReference('species-'.$faker->numberBetween(56, 73)));
            $individual->setUser($this->getReference('user-'.$faker->randomDigit));
            $individual->setStation($this->getReference('station-'.$faker->randomDigit));

            $manager->persist($individual);

            $this->addReference(sprintf('individual-%d', $c), $individual);
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
            StationFixtures::class,
        ];
    }
}

