<?php

namespace App\Helper;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

trait ImportCommandTrait
{
    /**
     * @return int|void
     */
    public function importEventSpeciesAndPeriods(
        Application $application,
        QuestionHelper $helper,
        InputInterface $input,
        OutputInterface $output,
        bool $defaultAnswer = false
    ) {
        $mustUpdateEventSpecies = $input->getArgument('importEventSpeciesAndPeriods');
        if (null === $mustUpdateEventSpecies) {
            $choicesString = $defaultAnswer ? '[Y/n]' : '[y/N]';
            $question = new ConfirmationQuestion('Update EventSpecies now? '.$choicesString, $defaultAnswer);
            $mustUpdateEventSpecies = $helper->ask($input, $output, $question);
        }
        if ($mustUpdateEventSpecies) {
            $EventSpeciesMigrateCommandNames = [
                'ods:generate:eventspecies',
                'ods:import:periods',
                'ods:import:featured-species-dates',
            ];

            foreach ($EventSpeciesMigrateCommandNames as $commandName) {
                switch ($commandName) {
                    case 'ods:import:periods':
                        $input = new ArrayInput(['periodTypeFromCommand' => 'b']);
                        break;
                    case 'ods:import:featured-species-dates':
                        $input = new ArrayInput(['cleanFeaturedSpeciesDates' => true]);
                        break;
                    default:
                        $input = new ArrayInput([]);
                        break;
                }
                $command = $application->find($commandName);
                $returnCode = $this->runCommand($command, $input, $output);
                if (0 !== $returnCode) {
                    $output->writeln(sprintf("<error>\n  Something went wrong with \"%s\"\n</error>", $commandName));

                    return 1;
                }

                $output->writeln(sprintf("<info>\n  ...\"%s\" done.\n</info>", $commandName));
            }

            $output->writeln("\n...EventSpeciesImport ...Done.\n");

            return 0;
        }
    }

    /**
     * @return int|void
     */
    private function runCommand(
        Command $command,
        InputInterface $input,
        OutputInterface $output
    ) {
        try {
            return $command->run($input, $output);
        } catch (\Exception $e) {
            $output->writeln($e);
        }
    }
}
