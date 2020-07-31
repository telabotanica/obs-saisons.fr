<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OdsImportObsCommand extends Command
{
    protected static $defaultName = 'ods:import:obs';

    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

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
        $occurrences = $conn->fetchAll(
            'SELECT e.ot_valeur AS evenement,
                ou.name AS occurence_user_display_name,
                oo_date AS date,
                oo_commentaire AS details,                
                oe_nom_scientifique AS nom_scientifique,
                os_ce_commune AS station_insee_code,
                su.name AS station_user_display_name,
                os_nom AS station_name,
                os_latitude AS station_latitude,
                os_longitude AS station_longitude,
                os_altitude AS station_altitude
            FROM ods_observations
                LEFT JOIN ods_individus ON oo_ce_individu = oi_id_individu
                LEFT JOIN ods_especes ON oi_ce_espece = oe_id_espece
                LEFT JOIN ods_stations ON oi_ce_station = os_id_station
                LEFT JOIN drupal_users ou ON oo_ce_participant = ou.uid
                LEFT JOIN drupal_users su ON os_ce_participant = su.uid
                LEFT JOIN ods_communes ON oc_code_insee = os_ce_commune
                LEFT JOIN ods_triples e ON e.ot_id_triple = oo_ce_evenement
                LEFT JOIN ods_triples m ON m.ot_id_triple = os_ce_environnement
            WHERE substring(os_ce_commune, 1, 2)
                AND `oo_date` != \'0000-00-00\'
                AND os_ce_participant != 4'
        );

        foreach ($occurrences as $occurrence) {
            $stadeBbch = null;
            preg_match('/stade ([\d]{2})$/', $occurrence['evenement'], $matches);

            if (isset($matches[1])) {
                $stadeBbch = $matches[1];
            }
            $io->text('Stade BBCH : '.$stadeBbch);
        }

        // prendre exemple sur OdsImportIndividualCommand.php

        $io->success('Count occurrences: '.count($occurrences));

        return 0;
    }
}
