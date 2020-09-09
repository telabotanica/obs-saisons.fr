<?php

namespace App\Command;

use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Station;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetStationLastActivity extends Command
{
    protected static $defaultName = 'ods:set:stations_last_activity';

    private $em;
    private $io;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('First set stations last Activity')
            ->setHelp('First set stations last Activity');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $stations = $this->em->getRepository(Station::class)->findAll();
        $bar = new ProgressBar($output, count($stations));
        foreach ($stations as $station) {
            $bar->advance();
            $lastActivity = $station->getUpdatedAt();
            try {
                $lastObsDateFromDB = $this->em->createQueryBuilder()
                    ->select('MAX(o.createdAt)')
                    ->from(Observation::class, 'o')
                    ->leftJoin(Individual::class, 'i', Expr\Join::WITH, 'o.individual = i.id')
                    ->where('i.station = :station')
                    ->setParameter('station', $station)
                    ->getQuery()
                    ->getSingleScalarResult()
                ;
                if (!empty($lastObsDateFromDB)) {
                    $lastObservationDate = new DateTimeImmutable($lastObsDateFromDB);
                    if ($lastObservationDate > $lastActivity) {
                        $lastActivity = $lastObservationDate;
                    }
                }
            } catch (\Exception $e) {
                $this->io->error(print_r($e));
            }

            $station->setLastActivity($lastActivity);
            $this->em->persist($station);
        }
        $bar->finish();
        $this->em->flush();

        return 0;
    }
}
