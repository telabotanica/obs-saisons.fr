<?php

namespace App\Command;

use App\Entity\Species;
use App\Entity\TypeSpecies;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SpeciesImportCommand extends Command
{
    protected static $defaultName = 'ods:import:events';

    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import species')
            ->setHelp('Import species');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $typeSpeciesRepository = $this->manager->getRepository(TypeSpecies::class);

        $speciesData = json_decode(file_get_contents('src/Ressources/ods_species.json'));

        foreach ($speciesData as $singleSpeciesData) {
            $output->writeln('Creating : '.$singleSpeciesData->vernacular_name);

            /**
             * @var TypeSpecies
             */
            $typeSpecies = $typeSpeciesRepository->findOneByName($singleSpeciesData->type_name);

            if (empty($typeSpecies)) {
                $output->writeln('type not found : '.$singleSpeciesData->type_name);

                return 1;
            }

            $species = new Species();
            $species->setVernacularName($singleSpeciesData->vernacular_name);
            $species->setScientificName($singleSpeciesData->scientific_name);
            $species->setDescription($singleSpeciesData->description);
            $species->setType($typeSpecies);
            $species->setIsActive($singleSpeciesData->is_active);
            $species->setPicture($singleSpeciesData->picture);

            $this->manager->persist($species);
            $output->writeln('...Ok.');
        }

        $this->manager->flush();
        $output->writeln('Done.');

        return 0;
    }
}
