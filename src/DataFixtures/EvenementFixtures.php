<?php

namespace App\DataFixtures;

use App\Entity\Evenement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class EvenementFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $evenementsData = json_decode(file_get_contents('src/Ressources/ods_evenements.json'));

        foreach ($evenementsData as $i => $evenementData) {
            $evenement = new Evenement();
            $evenement->setStadeBbch($evenementData->stade_bbch);
            $evenement->setNom($evenementData->nom);
            $evenement->setDescription($evenementData->description);
            $evenement->setIsObservable($evenementData->is_observable);

            $manager->persist($evenement);

            $this->addReference(sprintf('evenement-%d', $i + 1), $evenement);
        }

        $manager->flush();
    }
}
