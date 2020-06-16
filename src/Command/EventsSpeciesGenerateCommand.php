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
    protected static $defaultName = 'ods:generate:eventspecies';

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
        $allEventSpecies = $this->manager->getRepository(EventSpecies::class)->findAll();
        foreach ($allEventSpecies as $eventSpecies) {
            $this->manager->remove($eventSpecies);
        }
        $cmd = $this->manager->getClassMetadata(EventSpecies::class);
        $this->manager->getConnection()->exec('ALTER TABLE '.$cmd->getTableName().' AUTO_INCREMENT = 1;');
        $this->manager->flush();

        $speciesRepository = $this->manager->getRepository(Species::class);
        $eventRepository = $this->manager->getRepository(Event::class);
        $speciesData = json_decode(file_get_contents('src/Ressources/ods_species.json'));
        if (!$speciesData) {
            $output->writeln("<error>\n  couldn’t read species file\n</error>");

            return 1;
        }

        foreach ($speciesData as $speciesDatum) {
            $species = $speciesRepository->findOneBy(['scientific_name' => $speciesDatum->scientific_name]);

            if (!$species) {
                $output->writeln('species not found : '.$speciesDatum->scientific_name);

                return 1;
            }

            $output->writeln('Creating eventSpecies for species: '.$species->getVernacularName());

            foreach (explode(',', $speciesDatum->bbch_list) as $stadeBbch) {
                // animals doesn't have BBCH code
                if (null == $stadeBbch) {
                    $event = $eventRepository->findOneBy(['name' => '1ère apparition']);
                } else {
                    $event = $eventRepository->findOneBy(['stade_bbch' => $stadeBbch]);
                }

                if (!$event) {
                    $output->writeln('Stade BBCH not found : '.$stadeBbch);

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
