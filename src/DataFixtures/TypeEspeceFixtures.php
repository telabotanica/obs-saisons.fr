<?php

namespace App\DataFixtures;

use App\Entity\TypeEspece;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class TypeEspeceFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $typesEspecesData = json_decode(file_get_contents('src/Ressources/ods_types_especes.json'));

        foreach ($typesEspecesData as $i => $typeEspeceData) {
            $typeEspece = new TypeEspece();
            $typeEspece->setNom($typeEspeceData->nom);
            $typeEspece->setReigne($typeEspeceData->reigne);

            $manager->persist($typeEspece);

            // there are 7 typeEspeces (const)
            $this->addReference(sprintf('typesEspece-%d', $i + 1), $typeEspece);
        }

        $manager->flush();
    }
}
