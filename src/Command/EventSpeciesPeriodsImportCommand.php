<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\EventSpecies;
use App\Entity\Species;
use App\Service\HandleCsvFile as HandleCsvFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class EventSpeciesPeriodsImportCommand extends Command
{
    protected static $defaultName = 'ods:import:periods';

    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import events species periods')
            ->setHelp('Import events species periods');
        $this
            ->addArgument(
                'periodTypeFromCommand',
                InputArgument::OPTIONAL,
                'set periods type (s : stages periods, a : observations alerts periods, b : both), if this command is ran from other command'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $periodType = $input->getArgument('periodTypeFromCommand');
        if (!$periodType) {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'Please select periods types (s : stages periods, a : observations alerts periods, b : both / default to b : both)',
                ['s', 'a', 'b'],
                2
            );

            $question->setMultiselect(true);
            $question->setErrorMessage('Period type %s is invalid.');

            $periodType = $helper->ask($input, $output, $question)[0];
            $output->writeln('You have just selected: '.$periodType);
        }
        $output->writeln('Importing periods :');

        $speciesRepository = $this->manager->getRepository(Species::class);
        $eventRepository = $this->manager->getRepository(Event::class);
        $eventSpeciesRepository = $this->manager->getRepository(EventSpecies::class);

        $speciesPeriods = (new HandleCsvFile())->parseCsv('src/Ressources/calendar_periods.csv');
        if (empty($speciesPeriods) || !is_array($speciesPeriods)) {
            $output->writeln("<error>\n  couldn’t read the csv file\n</error>");

            return 1;
        }

        $answerOutput = [
            's' => 'stages periods',
            'a' => 'observations alerts periods',
            'b' => 'stages & observations alerts periods',
        ];

        foreach ($speciesPeriods as $singleSpeciesPeriods) {
            $species = $speciesRepository->findOneBy(['scientific_name' => $singleSpeciesPeriods['species_name']]);
            if (!$species) {
                $output->writeln(sprintf("<error>\n  species %d not found\n</error>", $singleSpeciesPeriods['species_name']));

                return 1;
            }

            if ('apparition' === $singleSpeciesPeriods['bbch_code']) {
                // animals doesn't have BBCH code
                $event = $eventRepository->findOneBy(['name' => '1ère apparition']);
            } else {
                $event = $eventRepository->findOneBy(['stade_bbch' => $singleSpeciesPeriods['bbch_code']]);
            }
            if (!$event) {
                $output->writeln(sprintf("<error>\n  event %d not found\n</error>", $singleSpeciesPeriods['bbch_code']));

                return 1;
            }

            $eventSpeciesArray = $eventSpeciesRepository->findBy(['species' => $species, 'event' => $event]);
            if (empty($eventSpeciesArray) || empty($eventSpeciesArray[0])) {
                $output->writeln(sprintf("<error>\n  event species for species with id %d and event with id %d not found\n</error>", $species->getId(), $event->getId()));

                return 1;
            }
            $eventSpecies = $eventSpeciesArray[0];

            if ('a' !== $periodType) {
                if (!$singleSpeciesPeriods['percentile_5'] && !$singleSpeciesPeriods['percentile_95']) {
                    $singleSpeciesPeriods['percentile_5'] = $singleSpeciesPeriods['percentile_95'] = null;
                }
                if (!$singleSpeciesPeriods['percentile_25'] && !$singleSpeciesPeriods['percentile_75']) {
                    $singleSpeciesPeriods['percentile_25'] = $singleSpeciesPeriods['percentile_75'] = null;
                }

                $eventSpecies->setPercentile5($singleSpeciesPeriods['percentile_5']);
                $eventSpecies->setPercentile95($singleSpeciesPeriods['percentile_95']);
                $eventSpecies->setPercentile25($singleSpeciesPeriods['percentile_25']);
                $eventSpecies->setPercentile75($singleSpeciesPeriods['percentile_75']);
            }

            if ('s' !== $periodType) {
                if (!$singleSpeciesPeriods['aberration_start_day'] && !$singleSpeciesPeriods['aberration_end_day']) {
                    $singleSpeciesPeriods['aberration_start_day'] = $singleSpeciesPeriods['aberration_end_day'] = null;
                }

                $eventSpecies->setAberrationStartDay($singleSpeciesPeriods['aberration_start_day']);
                $eventSpecies->setAberrationEndDay($singleSpeciesPeriods['aberration_end_day']);
            }

            $this->manager->persist($eventSpecies);

            $output->writeln(sprintf('<info>  updated %s for species with id %d and event with id %d</info>', $answerOutput[$periodType], $species->getId(), $event->getId()));
        }

        $this->manager->flush();

        $output->writeln('Done.');

        return 0;
    }
}
