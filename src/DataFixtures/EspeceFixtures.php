<?php

namespace App\DataFixtures;

use App\Entity\Espece;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker;

class EspeceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('fr_FR');
        $especesData = json_decode(file_get_contents('src/Ressources/ods_especes.json'));
        foreach ($especesData as $i => $especeData) {
            $espece = new Espece();
            $espece->setNomVernaculaire($especeData->nom_vernaculaire);
            $espece->setNomScientifique($especeData->nom_scientifique);
            $espece->setDescription($especeData->description);
            $espece->setType($this->getReference(sprintf('typesEspece-%d', $especeData->type_id)));
            $espece->setIsActive($especeData->is_active);
            $espece->setPhoto($faker->imageUrl(800, 600, 'nature'));


            $manager->persist($espece);

            $this->addReference(sprintf('espece-%d', $i + 1), $espece);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [
            TypeEspeceFixtures::class,
        ];
    }
}
