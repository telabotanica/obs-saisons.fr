<?php

namespace App\Command;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventsImportCommand extends Command
{
    protected static $defaultName = 'events:import';

    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import events')
            ->setHelp('Import events');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $eventsData = json_decode(file_get_contents('src/Ressources/ods_events.json'));

        foreach ($eventsData as $eventData) {
            $output->writeln('Creating : '.$eventData->name.' stade '.$eventData->stade_bbch);

            $event = new Event();
            $event->setStadeBbch($eventData->stade_bbch);
            $event->setName($eventData->name);
            $event->setDescription($eventData->description);
            $event->setIsObservable($eventData->is_observable);

            $this->manager->persist($event);
            $output->writeln('...Ok.');
        }

        $this->manager->flush();
        $output->writeln('Done.');

        return 0;
    }
}
