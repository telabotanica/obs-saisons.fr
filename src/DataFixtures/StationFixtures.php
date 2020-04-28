<?php

namespace App\DataFixtures;

use App\Entity\Station;
use App\Service\SlugGenerator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker;

class StationFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');
        $slugGenerator = new SlugGenerator();

        for ($i = 0; $i < 10; ++$i) {
            $name = $faker->sentence(6, true);
            $station = new Station();
            $station->setName($name);
            $station->setSlug($slugGenerator->slugify($name));
            $station->setDescription($faker->text(200));
            $station->setUser($this->getReference('user-'.$faker->randomDigit));
            $station->setIsPrivate($faker->boolean);
            $station->setHeaderImage($faker->imageUrl(800, 600, 'nature'));
            $station->setLocality($faker->city);
            $station->setHabitat($faker->randomElement(['Ville', 'Jardin/parc', 'Forêt', 'Champ/prairie', 'Village']));
            $station->setLatitude($faker->randomFloat(8.5, -90, 90));
            $station->setLongitude($faker->randomFloat(8.5, -180, 180));
            $station->setAltitude($faker->numberBetween(200, 1500));
            $station->setInseeCode(strval(substr($faker->departmentNumber.$faker->randomNumber(3, true), 0, 5)));

            $manager->persist($station);

            $this->addReference(sprintf('station-%d', $i), $station);
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
        ];
    }
}
