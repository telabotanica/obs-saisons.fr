<?php

namespace App\Command;

use App\Entity\Station;
use App\Entity\User;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OdsImportStationsCommand extends Command
{
    protected static $defaultName = 'ods:import:stations';

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
            ->setDescription('Import stations from legacy ODS database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $conn = $this->container->get('doctrine')->getConnection('ods_legacy');

        $importedStations = $conn->fetchAll(
            'SELECT `os_id_station` AS id,
                `os_nom` AS name,
                `name` AS user_display_name,
                `os_latitude` AS latitude,
                `os_longitude` AS longitude,
                `os_altitude` AS altitude,
                `oc_nom` AS locality,
                `os_ce_commune` AS insee_code,
                IF((`ot_id_triple` IS NULL),\'autre\',`ot_valeur`) AS habitat,
                `os_commentaire` AS description
            FROM ods_stations
            LEFT JOIN drupal_users ON `os_ce_participant` = `uid`
            LEFT JOIN ods_communes ON `os_ce_commune` = `oc_code_insee`
            LEFT JOIN ods_triples ON `os_ce_environnement` = `ot_id_triple`
            WHERE `os_id_station` IN (
                SELECT `oi_ce_station`
                FROM ods_observations
                LEFT JOIN ods_individus ON `oo_ce_individu` = `oi_id_individu`
                LEFT JOIN `ods_stations` ON `oi_ce_station` = `os_id_station`
                WHERE `oo_date` != \'0000-00-00\'
                AND `os_ce_participant` != 4
                GROUP BY `oi_ce_station`
            )
            AND `os_latitude` != 0
            AND `os_latitude` != \'\'
            AND `os_longitude` != 0
            AND `os_longitude` != \'\'
            AND `os_altitude` != \'\'
            AND `os_nom` != \'\'
            AND `oc_code_insee` != \'\'
            GROUP BY `os_ce_commune`,`os_ce_participant`,`os_nom`,`os_latitude`,`os_longitude`,`os_altitude`'
        );

        foreach ($importedStations as $importedStation) {
            $this->io->text('creating station : '.$importedStation['name']);

            $station = new Station();
            $station->setUser(
                $this->getStationUser($importedStation['user_display_name'])
            );
            $station->setIsPrivate(false);
            $station->setSlug(
                (new SlugGenerator())->slugify(
                    str_replace('/', '_', $importedStation['name'])
                )
            );
            $station->setLocality($importedStation['locality']);
            $station->setHabitat($importedStation['habitat']);
            $station->setHeaderImage(null);
            $station->setName($importedStation['name']);
            $station->setDescription($importedStation['description']);
            $station->setLatitude($importedStation['latitude']);
            $station->setLongitude($importedStation['longitude']);
            $station->setAltitude($importedStation['altitude']);
            $station->setInseeCode($importedStation['insee_code']);
            $station->setCreatedAt(new \DateTime());

            $this->em->persist($station);

            $this->io->text('...Ok.');
        }

        $this->em->flush();

        $this->io->success('Count stations: '.count($importedStations));

        return 0;
    }

    private function getStationUser(?string $importedStationUserDisplayName)
    {// find station creator user
        $user = null;
        if (null != $importedStationUserDisplayName) {
            $this->io->text('Created by : '.$importedStationUserDisplayName);
            $user = $this->userRepository->findOneBy(['displayName' => $importedStationUserDisplayName]);
        }
        if (null === $user) {
            return $this->admin;
        }

        return $user;
    }
}
