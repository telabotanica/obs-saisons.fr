<?php

namespace App\DataFixtures;

use App\Entity\Individual;
use App\Entity\Species;
use App\Entity\TypeSpecies;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class IndividualFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');
        $speciesRepository = $manager->getRepository(Species::class);
        $typeSpeciesRepository = $manager->getRepository(TypeSpecies::class);
        $plants = $typeSpeciesRepository->findByReign(TypeSpecies::REIGN_PLANTS);
        $animals = $typeSpeciesRepository->findByReign(TypeSpecies::REIGN_ANIMALS);

        for ($i = 0; $i < 76; ++$i) {
            $individual = new Individual();
            $individual->setName($faker->sentence(6, true));
            $individual->setSpecies($faker->randomElement($speciesRepository->findAllByTypeSpecies($faker->randomElement($plants))));
            $individual->setUser($this->getReference('user-'.$faker->randomDigit));
            $individual->setDetails($faker->text(200));
            $individual->setStation($this->getReference('station-'.$faker->randomDigit));
            $individual->setCreatedAt($faker->dateTimeThisDecade('now', 'Europe/Paris'));
            $individual->setIsDead(false);

            $manager->persist($individual);

            $this->addReference(sprintf('individual-%d', $i), $individual);
        }

        for ($c = 76; $c < 100; ++$c) {
            $individual = new Individual();
            $individual->setName($faker->sentence(6, true));
            $individual->setSpecies($faker->randomElement($speciesRepository->findAllByTypeSpecies($faker->randomElement($animals))));
            $individual->setUser($this->getReference('user-'.$faker->randomDigit));
            $individual->setDetails($faker->text(200));
            $individual->setStation($this->getReference('station-'.$faker->randomDigit));
            $individual->setCreatedAt($faker->dateTimeThisDecade('now', 'Europe/Paris'));
            $individual->setIsDead(true);

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
            StationFixtures::class,
            OdsStaticDataFixtures::class,
        ];
    }
}
