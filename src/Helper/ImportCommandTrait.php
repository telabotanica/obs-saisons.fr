<?php

namespace App\Helper;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

trait ImportCommandTrait
{
    public $kernel;

    /**
     * @return int|void
     */
    public function importEventSpeciesAndPeriods(
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

                $returnCode = $this->runCommand($commandName, $input, $output);
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
        string $commandName,
        InputInterface $input,
        OutputInterface $output
    ) {
        $application = new Application($this->kernel);
        $output->writeln($commandName);
        try {
            return $application->find($commandName)->run($input, $output);
        } catch (\Exception $e) {
            $output->writeln($e);
        }
    }
}
