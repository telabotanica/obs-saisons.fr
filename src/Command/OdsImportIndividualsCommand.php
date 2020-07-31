<?php

namespace App\Command;

use App\Entity\Individual;
use App\Entity\Species;
use App\Entity\Station;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OdsImportIndividualsCommand extends Command
{
    protected static $defaultName = 'ods:import:individuals';

    private $em;
    private $container;
    private $userRepository;
    private $admin;
    private $io;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->container = $container;
        $this->userRepository = $this->em->getRepository(User::class);
        $this->admin = $this->userRepository->findOneBy(['email' => 'contact@obs-saisons.fr']);

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

        $conn = $this->container->get('doctrine')->getConnection('ods_legacy');

        $importedIndividuals = $conn->fetchAll(
            'SELECT oi_nom AS name,
                oe_nom_scientifique AS species_scientific_name,
                os_ce_commune AS station_insee_code,
                name AS user_display_name,
                os_nom AS station_name,
                os_latitude AS station_latitude,
                os_longitude AS station_longitude,
                os_altitude AS station_altitude
            FROM ods_individus
                LEFT JOIN ods_stations ON oi_ce_station = os_id_station
                LEFT JOIN ods_especes ON oi_ce_espece = oe_id_espece
                LEFT JOIN drupal_users ON os_ce_participant = uid
            WHERE `oi_id_individu` IN (
                SELECT oo_ce_individu
                FROM ods_observations
                    LEFT JOIN ods_individus on oo_ce_individu = oi_id_individu
                WHERE `oo_date` != \'0000-00-00\'
                    AND oo_ce_participant != 4
                GROUP BY oo_ce_individu
            )
            AND os_ce_participant != 4'
        );

        $this->io->success('Count individuals before persist: '.count($importedIndividuals));

        foreach ($importedIndividuals as $importedIndividual) {
            //$this->io->text('Creating individual : '.$importedIndividual['name']);

            $individual = new Individual();
            $species = $this->getIndividualSpecies($importedIndividual['species_scientific_name']);
            $user = $this->getStationUser($importedIndividual['user_display_name']);
            $station = $this->getIndividualStation($importedIndividual, $user);
            if (!$species) {
                $this->io->error('Species for '.$importedIndividual['name'].' not found in new ODS DB');
            } elseif (!$user) {
                $this->io->error('User for '.$importedIndividual['name'].' not found in new ODS DB');
            } elseif (!$station) {
                $this->io->error('Station for '.$importedIndividual['name'].' not found in new ODS DB');
            } else {
                /*$individual->setSpecies($species);
                $individual->setStation($station);
                $individual->setUser($user);
                $individual->setName($importedIndividual['name']);
                $individual->setCreatedAt(new \DateTime());
                $this->em->persist($individual);*/
                //$this->io->success('got individual: '.$importedIndividual['name']);
            }
        }

        $this->io->success('Count individuals: '.count($importedIndividuals));

        return 0;
    }

    private function getIndividualSpecies(?string $importedIndividualSpeciesScientificName)
    {
        if (!isset($importedIndividualSpeciesScientificName)) {
            $this->io->text('Species not found in legacy ODS DB');

            return null;
        }

        return $this->em->getRepository(Species::class)->findOneBy(['scientific_name' => $importedIndividualSpeciesScientificName]);
    }

    private function getIndividualStation(array $importedIndividual, ?User $user)
    {
        $importedStation = [
            'inseeCode' => $importedIndividual['station_insee_code'],
            'name' => $importedIndividual['station_name'],
            'user' => $user,
            'latitude' => $importedIndividual['station_latitude'],
            'longitude' => $importedIndividual['station_longitude'],
            'altitude' => $importedIndividual['station_altitude'],
        ];
        foreach ($importedStation as $key => $importedStationCriteria) {
            if (!isset($importedStationCriteria)) {
                $this->io->text($key.' not found for individual station');

                return null;
            }
        }

        return $this->em->getRepository(Station::class)
            ->findOneBy($importedStation);
    }

    private function getStationUser(?string $importedStationUserDisplayName)
    {// find station creator user
        $user = null;
        if (null != $importedStationUserDisplayName) {
            //$this->io->text('Created by : '.$importedStationUserDisplayName);
            $user = $this->userRepository->findOneBy(['displayName' => $importedStationUserDisplayName]);
        }
        if (null === $user) {
            return $this->admin;
        }

        return $user;
    }
}
