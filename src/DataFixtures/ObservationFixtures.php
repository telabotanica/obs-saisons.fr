<?php

namespace App\DataFixtures;

use App\Entity\Observation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker;

class ObservationFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');
        for ($i = 0; $i < 750; ++$i) {
            $nom = $faker->sentence(6, true);
            $observation = new Observation();
            $observation->setIndividu($this->getReference('individu-'.$faker->numberBetween(0, 74)));
            $observation->setEvenement($this->getReference('evenement-'.$faker->numberBetween(1, 7)));
            $observation->setUser($this->getReference('user-'.$faker->randomDigit));
            $observation->setPhoto($faker->imageUrl(800, 600, 'nature'));
            $observation->setDateObs($faker->dateTimeThisDecade('now', 'Europe/Paris'));

            $manager->persist($observation);

            $this->addReference(sprintf('observation-%d', $i), $observation);
        }

        for ($c = 750; $c < 1000; ++$c) {
            $nom = $faker->sentence(6, true);
            $observation = new Observation();
            $observation->setIndividu($this->getReference('individu-'.$faker->numberBetween(75, 99)));
            $observation->setEvenement($this->getReference('evenement-8'));
            $observation->setUser($this->getReference('user-'.$faker->randomDigit));
            $observation->setPhoto($faker->imageUrl(800, 600, 'nature'));
            $observation->setDateObs($faker->dateTimeThisDecade('now', 'Europe/Paris'));

            $manager->persist($observation);

            $this->addReference(sprintf('observation-%d', $c), $observation);
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
            EvenementFixtures::class,
            IndividuFixtures::class,
        ];
    }
}
