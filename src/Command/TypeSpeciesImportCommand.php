<?php

namespace App\Command;

use App\Entity\TypeSpecies;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TypeSpeciesImportCommand extends Command
{
    protected static $defaultName = 'ods:import:typespecies';

    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import type species')
            ->setHelp('Import type species');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $typeSpeciesData = json_decode(file_get_contents('src/Ressources/ods_types_species.json'));

        foreach ($typeSpeciesData as $singleTypeSpeciesData) {
            $output->writeln('Creating : '.$singleTypeSpeciesData->name);

            $typeSpecies = new TypeSpecies();
            $typeSpecies->setName($singleTypeSpeciesData->name);
            $typeSpecies->setReign($singleTypeSpeciesData->reign);

            $this->manager->persist($typeSpecies);
            $output->writeln('...Ok.');
        }

        $this->manager->flush();
        $output->writeln('Done.');

        return 0;
    }
}
