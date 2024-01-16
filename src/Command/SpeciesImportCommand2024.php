<?php

namespace App\Command;

use App\Entity\Individual;
use App\Entity\Species;
use App\Entity\TypeSpecies;
use App\Helper\ImportCommandTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class SpeciesImportCommand2024 extends Command
{
    use ImportCommandTrait;

    protected static $defaultName = 'ods:import:species2024';

    private $io;
    public $em;
    private $individualsSpeciesBackup;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import species')
            ->setHelp('Import species')
        ;
        $this->addArgument(
            'importEventSpeciesAndPeriods',
            InputArgument::OPTIONAL,
            'import EventSpecies and fill periods [y/N]'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion('Cleaning previous Species? [Y/n]', true);
        $mustClean = $helper->ask($input, $output, $question);

        if ($mustClean) {
            // make it possible to update species in individuals table
            $this->individualsSpeciesBackup = [];
            self::setIndividualsSpeciesBackup();
            $this->resetSpecies();
        }

        $typeSpeciesRepository = $this->em->getRepository(TypeSpecies::class);

        //updating species
        $speciesData = json_decode(file_get_contents('src/Ressources/ods_species_2024.json'));

        foreach ($speciesData as $singleSpeciesData) {
            $this->io->section('Creating : '.$singleSpeciesData->vernacular_name);

            /**
             * @var TypeSpecies
             */
            $typeSpecies = $typeSpeciesRepository->findOneByName($singleSpeciesData->type_name);

            if (empty($typeSpecies)) {
                $this->io->error('type not found : '.$singleSpeciesData->type_name);

                return 1;
            }

            $species = new Species();
            $species->setVernacularName($singleSpeciesData->vernacular_name);
            $species->setScientificName($singleSpeciesData->scientific_name);
            $species->setDescription($singleSpeciesData->description);
            $species->setType($typeSpecies);
            $species->setIsActive($singleSpeciesData->is_active);
            $species->setPicture($singleSpeciesData->picture);

            $this->em->persist($species);
            $this->io->text('...Ok.');
        }

        $this->em->flush();

        if ($mustClean) {
            $this->updateIndividuals();
            // remind user to reset event-species if needed
            $this->io->newLine();
            $this->io->warning([
                'EventSpecies Table has to be updated',
                'If you wish to update both events and species',
                'make sure both updates are done',
                'otherwise you can run EventSpecies import commands now',
            ]);
            // from importCommandTrait
            $this->importEventSpeciesAndPeriods(
                $this->getApplication(),
                $helper,
                $input,
                $output,
                false
            );
        }

        $this->io->newLine();
        $this->io->text('Done.');

        return 0;
    }

    // getting individuals species references before importing species
    private function setIndividualsSpeciesBackup(): self
    {
        $allIndividuals = $this->em->getRepository(Individual::class)->findAll();

        foreach ($allIndividuals as $individual) {
            $this->individualsSpeciesBackup[] = [
                'individual' => $individual,
                'vernacularName' => $individual->getSpecies()->getVernacularName(),
            ];
        }

        return $this;
    }

    private function resetSpecies()
    {
        $allSpecies = $this->em->getRepository(Species::class)->findAll();
        foreach ($allSpecies as $species) {
            $this->em->remove($species);
        }
        $this->io->success('Species cleaned');
        $this->em->getConnection()->exec('SET foreign_key_checks = 0;');
    }

    private function updateIndividuals()
    {
        //updating individuals
        $this->io->section('Updating individuals...');
        foreach ($this->individualsSpeciesBackup as $individualSpeciesBackup) {
            $individual = $individualSpeciesBackup['individual'];
            $species = $this->em->getRepository(Species::class)
                ->findOneBy([
                    'vernacular_name' => $individualSpeciesBackup['vernacularName'],
                ])
            ;

            if (!$species) {
                $this->io->error(sprintf('species not found for individual, id: %d.', $individual->getId()));
            } else {
                $individual->setSpecies($species);

                $this->io->success(sprintf('updated species for individual, id: %d.', $individual->getId()));
            }
        }

        $this->em->flush();
        $this->em->getConnection()->exec('SET foreign_key_checks = 1;');

        $this->io->newLine();
        $this->io->text('...Ok.');
    }
}
