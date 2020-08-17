<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\EventSpecies;
use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OdsImportObsCommand extends Command
{
    protected static $defaultName = 'ods:import:obs';

    private $container;
    private $em;
    private $userRepository;
    private $admin;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em)
    {
        $this->container = $container;
        $this->em = $em;
        $this->userRepository = $this->em->getRepository(User::class);
        $this->admin = $this->userRepository->findOneBy(['email' => 'contact@obs-saisons.fr']);

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import occurrences from legacy ODS database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $conn = $this->container->get('doctrine')->getConnection('ods_legacy');

        // ajouter les individus à identifier par nom station et espèce
        $importingOccurrences = $conn->fetchAll(
            'SELECT oo_id_observation as obs_id,
                e.ot_valeur AS evenement,
                oo_date AS date,
                oo_commentaire AS details,                
                oe_nom_scientifique AS nom_scientifique,
                oo_absence_evenement AS is_missing,
                oo_date_saisie AS created_at,
                os_ce_commune AS station_insee_code,
                os_nom AS station_name,
                os_latitude AS station_latitude,
                os_longitude AS station_longitude,
                os_altitude AS station_altitude,
                oo_ce_individu AS individu_id,
                oi_ce_station AS station_id,
                oo_ce_participant AS user_id
            FROM ods_observations
                INNER JOIN ods_individus ON oo_ce_individu = oi_id_individu
                INNER JOIN ods_especes ON oi_ce_espece = oe_id_espece
                INNER JOIN ods_stations ON oi_ce_station = os_id_station
                INNER JOIN ods_communes ON oc_code_insee = os_ce_commune
                LEFT JOIN ods_triples e ON e.ot_id_triple = oo_ce_evenement
                LEFT JOIN ods_triples m ON m.ot_id_triple = os_ce_environnement
            WHERE substring(os_ce_commune, 1, 2)
            AND `oo_date` != \'0000-00-00\'
            AND os_ce_participant != 4
            AND oi_ce_espece != 0
            AND oi_ce_station != 0
            AND `os_latitude` != 0
            AND `os_latitude` != \'\'
            AND `os_longitude` != 0
            AND `os_longitude` != \'\'
            AND `os_altitude` != \'\'
            AND `os_nom` != \'\'
            AND `os_ce_commune` != \'\''
        );

        $bar = new ProgressBar($output, count($importingOccurrences));

        $eventsCatalog = [];
        $events = $this->em->getRepository(Event::class)->findAll();
        foreach ($events as $event) {
            $name = $event->getName();
            $stade = $event->getStadeBbch();

            if ($stade) {
                $eventsCatalog[$name.' stade '.$stade] = $event;
            } else {
                $eventsCatalog['1ere apparition'] = $event;
            }
        }

        $orphansObs = [];
        $successes = 0;
        foreach ($importingOccurrences as $importingOccurrence) {
            $bar->advance();
            // get individual
            $individual = $this->em->getRepository(Individual::class)
                ->findOneBy(['legacyId' => $importingOccurrence['individu_id']])
            ;
            if (!$individual) {
                $orphansObs[] = $importingOccurrence;
                continue;
            }

            // get user
            $user = $this->em->getRepository(User::class)->findOneBy(['legacyId' => $importingOccurrence['user_id']]);

            $occurrence = new Observation();
            $occurrence->setIndividual($individual);
            $occurrence->setEvent($eventsCatalog[$importingOccurrence['evenement']]);
            $occurrence->setUser($user ?? $this->admin);
            $occurrence->setDate(new \DateTime($importingOccurrence['date']));
            $occurrence->setIsMissing($importingOccurrence['is_missing']);
            $occurrence->setDetails($importingOccurrence['details']);
            $occurrence->setCreatedAt(new \DateTime($importingOccurrence['created_at']));

            $this->em->persist($occurrence);
            ++$successes;
        }

        foreach ($orphansObs as $orphanObs) {
            $io->text('orphan obs id: '.$orphanObs['obs_id']);
        }

        $bar->finish();
        $this->em->flush();
        $io->success("Imported occurrences: $successes/".count($importingOccurrences));

        return 0;
    }
}
