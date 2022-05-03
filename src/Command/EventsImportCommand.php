<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\Observation;
use App\Helper\ImportCommandTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class EventsImportCommand extends Command
{
    use ImportCommandTrait;

    protected static $defaultName = 'ods:import:events';

    private $io;
    public $em;
    private $observationsEventsBackup;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import events')
            ->setHelp('Import events')
        ;
        $this->addArgument(
            'importEventSpeciesAndPeriods',
            InputArgument::OPTIONAL,
            'import EventSpecies and fill periods [Y/n]'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion('Cleaning previous Events? [Y/n]', true);
        $mustClean = $helper->ask($input, $output, $question);

        if ($mustClean) {
            // make it possible to update species in individuals table
            $this->observationsEventsBackup = [];
            self::setObservationSpeciesBackup();
            $this->resetEvents();
        }

        //updating events
        $eventsData = json_decode(file_get_contents('src/Ressources/ods_events.json'));

        foreach ($eventsData as $eventData) {
            $this->io->section('Creating : '.$eventData->name.' stade '.$eventData->bbch_code);

            $event = new Event();
            $event->setStadeBbch($eventData->bbch_code);
            $event->setName($eventData->name);
            $event->setDescription($eventData->description);
            $event->setIsObservable($eventData->is_observable);

            $this->em->persist($event);
            $this->io->text('...Ok.');
        }

        $this->em->flush();

        if ($mustClean) {
            $this->updateObservations();
            // remind user to reset event-species if needed
            $this->io->newLine();
            $this->io->warning([
                'EventSpecies table has to be updated',
                'If you wish to update both Events and Species tables, make sure both are done before updating EventSpecies',
                'Otherwise you can run EventSpecies import commands now...',
            ]);
            // from importCommandTrait
            $this->importEventSpeciesAndPeriods(
                $this->getApplication(),
                $helper,
                $input,
                $output,
                true
            );
        }

        $this->io->newLine();
        $this->io->text('Done.');

        return 0;
    }

    // getting observations events references before importing events
    private function setObservationSpeciesBackup(): self
    {
        $allObservations = $this->em->getRepository(Observation::class)->findAll();
        $this->observationsEventsBackup = [];
        foreach ($allObservations as $observation) {
            $this->observationsEventsBackup[] = [
                'observation' => $observation,
                'stadeBbch' => $observation->getEvent()->getStadeBbch(),
            ];
        }

        return $this;
    }

    private function resetEvents()
    {
        $allEvents = $this->em->getRepository(Event::class)->findAll();
        foreach ($allEvents as $event) {
            $this->em->remove($event);
        }
        $this->io->success('Events cleaned');
        $this->em->getConnection()->exec('SET foreign_key_checks = 0;');
    }

    private function updateObservations()
    {
        $eventRepository = $this->em->getRepository(Event::class);
        //updating observations
        $this->io->section('Updating observations...');
        foreach ($this->observationsEventsBackup as $observationEventBackup) {
            $observation = $observationEventBackup['observation'];
            $event = null;
            if ($observationEventBackup['stadeBbch']) {
                $event = $eventRepository->findOneBy([
                    'bbch_code' => $observationEventBackup['stadeBbch'],
                ]);
            } else {
                $event = $eventRepository->findOneByName('1ère apparition');
            }

            if (!$event) {
                $this->io->error(sprintf('event not found for observation, id: %d.', $observation->getId()));
            } else {
                $observation->setEvent($event);

                $this->io->success(sprintf('updated event for observation, id: %d.', $observation->getId()));
            }
        }
        $this->em->flush();
        $this->em->getConnection()->exec('SET foreign_key_checks = 1;');

        $this->io->newLine();
        $this->io->text('...Ok.');
    }
}
