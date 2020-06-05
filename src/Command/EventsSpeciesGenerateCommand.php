<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\EventSpecies;
use App\Entity\Species;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventsSpeciesGenerateCommand extends Command
{
    protected static $defaultName = 'eventspecies:generate';

    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate eventSpecies')
            ->setHelp('Generate eventSpecies');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var Species[]
         */
        $allSpecies = $this->manager->getRepository(Species::class)->findAll();

        if (empty($allSpecies)) {
            $output->writeln('species not found');

            return 1;
        }

        $eventRepository = $this->manager->getRepository(Event::class);

        foreach ($allSpecies as $species) {
            $typeId = $species->getType()->getId();
            $speciesId = $species->getId();

            $eventsIds = [];
            if (empty($typeId)) {
                $output->writeln('type id not found : '.$typeId);

                return 1;
            }
            if (2 < $typeId) {
                $eventsIds = [8];
            } elseif (2 == $typeId) {
                $eventsIds = [3];
            } else {
                if (in_array($speciesId, EventSpecies::CONIFEROUS_ES_IDS['species'])) {
                    $eventsIds = EventSpecies::CONIFEROUS_ES_IDS['events'];
                } elseif (in_array($speciesId, EventSpecies::ONLY_FLOWERING_N_FRUITING_ES_IDS['species'])) {
                    $eventsIds = EventSpecies::ONLY_FLOWERING_N_FRUITING_ES_IDS['events'];
                } else {
                    $eventsIds = range(1, 7);
                }
            }

            $output->writeln('Creating eventSpecies for species: '.$species->getVernacularName());

            foreach ($eventsIds as $eventId) {
                /**
                 * @var Event
                 */
                $event = $eventRepository->find($eventId);

                if (empty($event)) {
                    $output->writeln(sprintf('event with id %d not found.', $eventId));

                    return 1;
                }

                $eventSpecies = new EventSpecies($event, $species);

                $message = '...for event: '.$event->getName();
                if ($event->getStadeBbch()) {
                    $message .= ' stade '.$event->getStadeBbch();
                }
                $output->writeln($message);

                $this->manager->persist($eventSpecies);
            }

            $output->writeln('...Ok.');
        }

        $this->manager->flush();
        $output->writeln('Done.');

        return 0;
    }
}
