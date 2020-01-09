<?php

namespace App\DataFixtures;

use App\Entity\EvenementEspece;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker;

class EvenementEspeceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');
        $evenementsEspecesReferences = [];
        // combining plant-events with plants
        $evenementsReferences = range(1, 7); // currently only event with reference 8 is for animals
        $especesReferences = range(1, 55); // currently 18 animal spiecies (the 6 last)
        foreach ($evenementsReferences as $evenementsReference) {
            foreach ($especesReferences as $especesReference) {
                $evenementsEspecesReferences[] = [
                    'evenement' => $evenementsReference,
                    'espece' => $especesReference,
                ];
            }
        }
        // combining animal-events with animals
        $especesReferences = range(56, 73);
        foreach ($especesReferences as $especesReference) {
            array_push($evenementsEspecesReferences, [
                    'evenement' => 8,
                    'espece' => $especesReference,
                ]);
        }

        foreach ($evenementsEspecesReferences as $i => $evenementsEspecesReference) {
            $evenement = $this->getReference('evenement-'.$evenementsEspecesReference['evenement']);
            $espece = $this->getReference('espece-'.$evenementsEspecesReference['espece']);
            $dateDebut = $faker->dateTimeBetween('0000-01-01', '0000-09-30', 'Europe/Paris');
            $dateFin = $faker->dateTimeInInterval($dateDebut, '+ 3 months', 'Europe/Paris');

            $evenementsEspeces = new EvenementEspece($evenement, $espece);
            $evenementsEspeces->setDescription($faker->paragraph(3, true));
            $evenementsEspeces->setPhoto($faker->imageUrl(800, 600, 'nature'));
            $evenementsEspeces->setDateDebut($dateDebut);
            $evenementsEspeces->setDateFin($dateFin);

            $manager->persist($evenementsEspeces);

            $this->addReference(sprintf('evenementsEspeces-%d', $i), $evenementsEspeces);
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
            EvenementFixtures::class,
        ];
    }
}
