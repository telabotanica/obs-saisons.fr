<?php

namespace App\Command;

use App\Entity\Individual;
use App\Entity\Species;
use App\Entity\Station;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class OdsImportIndividualsCommand extends Command
{
    protected static $defaultName = 'ods:import:individuals';

    private $em;
    private $managerRegistry;
    private $userRepository;
    private $io;

    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->managerRegistry = $managerRegistry;
        $this->userRepository = $this->em->getRepository(User::class);

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import individuals from legacy ODS database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $conn = $this->managerRegistry->getConnection('ods_legacy');

        $importedIndividuals = $conn->fetchAll(
            'SELECT
                   oi_id_individu as id, 
                   oi_nom AS name,
                   oi_ce_station AS station_id,
                   oe_nom_scientifique AS species_scientific_name,
                   os_ce_commune AS station_insee_code,
                   name AS user_display_name,
                   os_nom AS station_name,
                   os_latitude AS station_latitude,
                   os_longitude AS station_longitude,
                   os_altitude AS station_altitude,
                   obs.created_date AS created_at
            FROM ods_individus
            INNER JOIN ods_stations ON oi_ce_station = os_id_station
            INNER JOIN ods_communes ON `os_ce_commune` = `oc_code_insee`
            INNER JOIN ods_especes ON oi_ce_espece = oe_id_espece
            LEFT JOIN drupal_users ON os_ce_participant = uid
            INNER JOIN
                ( SELECT oo_ce_individu,
                      min(oo_date) AS created_date
                 FROM ods_observations
                 INNER JOIN ods_individus ON oo_ce_individu = oi_id_individu
                 INNER JOIN ods_stations ON oi_ce_station = os_id_station
                 WHERE `oo_date` != \'0000-00-00\'
                   AND oo_ce_participant != 4
                 GROUP BY oo_ce_individu ) obs ON oi_id_individu = obs.oo_ce_individu
            WHERE os_ce_participant != 4
            AND oi_ce_espece != 0
            AND oi_ce_station != 0
            AND `os_latitude` != 0
            AND `os_latitude` != \'\'
            AND `os_longitude` != 0
            AND `os_longitude` != \'\'
            AND `os_altitude` != \'\'
            AND `os_nom` != \'\'
            AND `os_ce_commune` != \'\'
            AND substring(os_ce_commune, 1, 2)
            AND `os_ce_participant` != 4'
        );

        $this->io->success('Count individuals before persist: '.count($importedIndividuals));

        $count = 0;
        $allMissingSpecies = [];
        $missingStations = [];
        $missingUsers = [];
        $orphanIndividuals = [];
        foreach ($importedIndividuals as $importedIndividual) {
            $this->io->text('Creating individual : '.$importedIndividual['name']);

            $individual = new Individual();

            $species = $this->getIndividualSpecies($importedIndividual['species_scientific_name']);
            if (!$species) {
                $this->io->error(sprintf(
                    'Species %s for individual %s (id: %d) not found in new ODS DB, skipping',
                    $importedIndividual['species_scientific_name'],
                    $importedIndividual['name'],
                    $importedIndividual['id']
                ));
                $allMissingSpecies[] = $importedIndividual['species_scientific_name'];
                $orphanIndividuals['missing_species'][] = $importedIndividual;
                continue;
            }

            $station = $this->getIndividualStation($importedIndividual['station_id']);
            if (!$station) {
                $this->io->error(sprintf(
                    'Station %s for individual %s (id: %d) not found in new ODS DB, skipping',
                    $importedIndividual['station_id'],
                    $importedIndividual['name'],
                    $importedIndividual['id']
                ));
                $missingStations[] = $importedIndividual['station_id'];
                $orphanIndividuals['missing_station'][] = $importedIndividual;
                continue;
            }

            $user = $station->getUser();
            if (!$user) {
                $this->io->error(sprintf(
                    'User %s for individual %s (id: %d) not found in new ODS DB, skipping',
                    $importedIndividual['user_display_name'],
                    $importedIndividual['name'],
                    $importedIndividual['id']
                ));
                $missingUsers[] = $importedIndividual['user_display_name'];
                $orphanIndividuals['missing_user'][] = $importedIndividual;
                continue;
            }

            $individual->setSpecies($species);
            $individual->setStation($station);
            $individual->setUser($user);
            $individual->setName($importedIndividual['name']);
            $individual->setCreatedAt(new \DateTime($importedIndividual['created_at']));
            $individual->setLegacyId($importedIndividual['id']);

            $this->em->persist($individual);
            ++$count;
            $this->io->text('...OK');
        }

        $allMissingSpecies = array_unique($allMissingSpecies);
        $missingStations = array_unique($missingStations);
        $missingUsers = array_unique($missingUsers);
        $this->io->caution(sprintf(
            'Missing species: %d, missing stations %d, missing users: %d',
            count($allMissingSpecies),
            count($missingStations),
            count($missingUsers)
        ));
        foreach ($allMissingSpecies as $missingSpecies) {
            $this->io->text('Missing species: '.$missingSpecies);
        }
        foreach ($missingStations as $missingStation) {
            $this->io->text('Missing station: '.$missingStation);
        }
        foreach ($missingUsers as $missingUser) {
            $this->io->text('Missing user: '.$missingUser);
        }
        foreach ($orphanIndividuals as $type => $orphanIndividual) {
            $this->io->caution('Orphans individuals '.$type.' count: '.count($orphanIndividuals[$type]));
        }

        $this->em->flush();
        $this->io->success('Imported individuals: '.$count.'/'.count($importedIndividuals));

        return 0;
    }

    private function getIndividualSpecies(?string $importedIndividualSpeciesScientificName)
    {
        if (!$importedIndividualSpeciesScientificName) {
            $this->io->text('Species not found in legacy ODS DB');

            return null;
        }

        return $this->em->getRepository(Species::class)
            ->findOneBy(['scientific_name' => $importedIndividualSpeciesScientificName])
        ;
    }

    private function getIndividualStation($legacy_station_id)
    {
        return $this->em->getRepository(Station::class)
            ->findOneBy(['legacyId' => $legacy_station_id])
        ;
    }
}
