<?php

namespace App\Command;

use App\Entity\Station;
use App\Entity\User;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class OdsImportStationsCommand extends Command
{
    protected static $defaultName = 'ods:import:stations';

    private $io;
    private $em;
    private $managerRegistry;
    private $slugGenerator;
    private $userRepository;
    private $admin;

    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $em, SlugGenerator $slugGenerator)
    {
        $this->em = $em;
        $this->managerRegistry = $managerRegistry;
        $this->slugGenerator = $slugGenerator;
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

        $conn = $this->managerRegistry->getConnection('ods_legacy');

        $importedStations = $conn->fetchAll(
            'SELECT `os_id_station` AS id,
                uid AS user_id,
                `os_nom` AS name,
                `name` AS user_display_name,
                `os_latitude` AS latitude,
                `os_longitude` AS longitude,
                `os_altitude` AS altitude,
                `oc_nom` AS locality,
                `os_ce_commune` AS insee_code,
                IF((`ot_id_triple` IS NULL),\'autre\',`ot_valeur`) AS habitat,
                `os_commentaire` AS description,
                stations.created_date AS createdAt
            FROM ods_stations
            LEFT JOIN drupal_users ON `os_ce_participant` = `uid`
            INNER JOIN ods_communes ON `os_ce_commune` = `oc_code_insee`
            LEFT JOIN ods_triples ON `os_ce_environnement` = `ot_id_triple`
            INNER JOIN
              ( SELECT `oi_ce_station`, MIN(oo_date) as created_date
               FROM ods_observations
               INNER JOIN ods_individus ON `oo_ce_individu` = `oi_id_individu`
               INNER JOIN `ods_stations` ON `oi_ce_station` = `os_id_station`
               WHERE `oo_date` != \'0000-00-00\'
                 AND `os_ce_participant` != 4
               GROUP BY `oi_ce_station`
              ) stations ON `os_id_station` = stations.`oi_ce_station`
            WHERE `os_latitude` != 0
            AND `os_latitude` != \'\'
            AND `os_longitude` != 0
            AND `os_longitude` != \'\'
            AND `os_altitude` != \'\'
            AND `os_nom` != \'\'
            AND `oc_code_insee` != \'\'
            AND substring(os_ce_commune, 1, 2)
            AND `os_ce_participant` != 4'
        );

        foreach ($importedStations as $importedStation) {
            $this->io->text('creating station : '.$importedStation['name']);

            $station = new Station();
            $station->setUser(
                $this->getStationUser($importedStation['user_id'])
            );
            $station->setIsPrivate(true);
            $station->setSlug(
                $this->slugGenerator->slugify(
                    str_replace('/', '_', $importedStation['locality'].' '.$importedStation['name'])
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
            $station->setCreatedAt(new \DateTime($importedStation['createdAt']));
            $station->setLegacyId($importedStation['id']);

            $this->em->persist($station);

            $this->io->text('...Ok.');
        }

        $this->em->flush();

        $this->io->success('Count stations: '.count($importedStations));

        return 0;
    }

    private function getStationUser($user_id)
    {
        // find station creator user, given to admin by default
        $user = $this->em->getRepository(User::class)->findOneBy(['legacyId' => $user_id]);

        return $user ?? $this->admin;
    }
}
