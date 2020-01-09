<?php

namespace App\DataFixtures;

use App\Entity\Individu;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker;

class IndividuFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 0; $i < 76; ++$i) {
            $individu = new Individu();
            $individu->setNom($faker->sentence(6, true));
            $individu->setEspece($this->getReference('espece-'.$faker->numberBetween(1, 55)));
            $individu->setUser($this->getReference('user-'.$faker->randomDigit));
            $individu->setStation($this->getReference('station-'.$faker->randomDigit));

            $manager->persist($individu);

            $this->addReference(sprintf('individu-%d', $i), $individu);
        }

        for ($c = 76; $c < 100; ++$c) {
            $individu = new Individu();
            $individu->setNom($faker->sentence(6, true));
            $individu->setEspece($this->getReference('espece-'.$faker->numberBetween(56, 73)));
            $individu->setUser($this->getReference('user-'.$faker->randomDigit));
            $individu->setStation($this->getReference('station-'.$faker->randomDigit));

            $manager->persist($individu);

            $this->addReference(sprintf('individu-%d', $c), $individu);
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
            EspeceFixtures::class,
            StationFixtures::class,
        ];
    }
}

