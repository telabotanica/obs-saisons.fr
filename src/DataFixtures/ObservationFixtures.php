<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\Observation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class ObservationFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');
        $eventRepository = $manager->getRepository(Event::class);

        for ($i = 0; $i < 750; ++$i) {
            $observation = new Observation();
            $observation->setIndividual($this->getReference('individual-'.$faker->numberBetween(0, 75)));
            $observation->setEvent($faker->randomElement($eventRepository->findAllWithBbch()));
            $observation->setUser($this->getReference('user-'.$faker->randomDigit));
            $observation->setPicture('/media/layout/image_station.png');
            $observation->setDate($faker->dateTimeThisDecade('now', 'Europe/Paris'));
            $observation->setCreatedAt($faker->dateTimeThisDecade('now', 'Europe/Paris'));
            $observation->setDetails($faker->text(200));
            $observation->setIsMissing($faker->boolean);

            $manager->persist($observation);

            $this->addReference(sprintf('observation-%d', $i), $observation);
        }

        for ($c = 750; $c < 1000; ++$c) {
            $observation = new Observation();
            $observation->setIndividual($this->getReference('individual-'.$faker->numberBetween(76, 99)));
            $observation->setEvent($faker->randomElement($eventRepository->findAllWithoutBbch()));
            $observation->setUser($this->getReference('user-'.$faker->randomDigit));
            $observation->setPicture('/media/layout/image_station.png');
            $observation->setDate($faker->dateTimeThisDecade('now', 'Europe/Paris'));
            $observation->setCreatedAt($faker->dateTimeThisDecade('now', 'Europe/Paris'));
            $observation->setDetails($faker->text(200));
            $observation->setIsMissing($faker->boolean);
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
            IndividualFixtures::class,
        ];
    }
}
