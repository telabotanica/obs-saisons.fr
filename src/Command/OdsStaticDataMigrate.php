<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OdsStaticDataMigrate extends Command
{
    protected static $defaultName = 'ods:bootstrap:all-static-data';

    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Bootstrap with all static data')
            ->setHelp('Bootstrap with all static data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $odsStaticDataMigrateCommandNames = [
            'ods:import:typespecies',
            'ods:import:species',
            'ods:import:events',
            'ods:generate:eventspecies',
            'ods:import:periods',
            'ods:import:featured-species-dates',
        ];

        foreach ($odsStaticDataMigrateCommandNames as $commandName) {
            if ('periods:import' === $commandName) {
                $input = new ArrayInput(['periodTypeFromCommand' => 'b']);
            }

            $returnCode = $this->runCommand($commandName, $input, $output);
            if (0 !== $returnCode) {
                $output->writeln(sprintf("<error>\n  Something went wrong with \"%s\"\n</error>", $commandName));

                return 1;
            }

            $output->writeln(sprintf("<info>\n  ...\"%s\" done.\n</info>", $commandName));
        }

        $output->writeln("\n...OdsStaticDataMigrate ...Done.\n");

        return 0;
    }

    private function runCommand(string $commandName, InputInterface $input, OutputInterface $output)
    {
        $command = $this->getApplication()->find($commandName);
        try {
            return $command->run($input, $output);
        } catch (\Exception $e) {
            $output->writeln($e);
        }
    }
}
