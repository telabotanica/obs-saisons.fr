<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\EventSpecies;
use App\Entity\Species;
use App\Service\HandleCsvFile as HandleCsvFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class OdsImportFeaturedSpeciesDatesCommand extends Command
{
    protected static $defaultName = 'ods:import:featured-species-dates';

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import featured species dates from file')
            ->setHelp('Import featured species dates from file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $featuredData = (new HandleCsvFile())->parseCsv('src/Ressources/ods_featured_dates.csv');
        if (empty($featuredData) || !is_array($featuredData)) {
            $output->writeln("<error>\n  couldn’t read the csv file\n</error>");

            return 1;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Cleaning previous featured dates? [Y/n]', true);

        if ($helper->ask($input, $output, $question)) {
            $allEventSpecies = $this->em->getRepository(EventSpecies::class)->findAll();
            foreach ($allEventSpecies as $eventSpecies) {
                $eventSpecies->setFeaturedStartDay(null);
                $eventSpecies->setFeaturedEndDay(null);
            }
            $this->em->flush();

            $output->writeln(sprintf('<info>%d featured dates cleaned</info>', count($allEventSpecies)));
        }

        foreach ($featuredData as $featured) {
            $start = intval($featured['start']);
            $end = intval($featured['end']);

            if ((!$start && '0' !== $featured['start']) || (!$end && '0' !== $featured['end'])) {
                $output->writeln(sprintf(
                    '<error>Wrong values for %s stade %s featured start %s (%d) end %s (%d)</error>',
                    $featured['species'],
                    $featured['stade'],
                    $featured['start'],
                    $start,
                    $featured['end'],
                    $end
                ));

                continue;
            }
            $output->writeln(sprintf(
                'Creating : %s stade %s featured start %d end %d',
                $featured['species'],
                $featured['stade'],
                $start,
                $end
            ));

            $species = $this->em->getRepository(Species::class)
                ->findOneBy(['scientific_name' => $featured['species']])
            ;
            if (!$species) {
                $output->writeln(sprintf('<error>species not found : %s</error>', $featured['species']));

                continue;
            }

            if ('apparition' === $featured['stade']) {
                $event = $this->em->getRepository(Event::class)
                    ->findOneBy(['name' => Event::ANIMALS_EVENT])
                ;
            } else {
                $event = $this->em->getRepository(Event::class)
                    ->findOneBy(['stade_bbch' => $featured['stade']])
                ;
            }
            if (!$event) {
                $output->writeln(sprintf('<error>event not found : %d</error>', $featured['stade']));

                continue;
            }

            $eventSpecies = $this->em->getRepository(EventSpecies::class)
                ->findOneBy(['species' => $species, 'event' => $event])
            ;
            if (!$eventSpecies) {
                $output->writeln(sprintf(
                    '<error>eventSpecies not found : %s %s %d</error>',
                    $species->getVernacularName(),
                    $event->getName(),
                    $event->getStadeBbch()
                ));

                continue;
            }
            $eventSpecies->setFeaturedStartDay($start);
            $eventSpecies->setFeaturedEndDay($end);

            $output->writeln('...Ok.');
        }

        $this->em->flush();

        $output->writeln('Done.');

        return 0;
    }
}
