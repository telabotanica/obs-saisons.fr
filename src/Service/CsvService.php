<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;

class CsvService
{

    public function csvAction(array $data): Response
    {
        $list = array(
            // Colonnes titre du csv
            ['Id',
//                'Image',
                'Date', 'IsMissing', 'Détails', 'Date de l\'observation', 'Mise à jour', 'Date de suppression', 'id de l\'individu', 'Nom de l\'individu', 'Détail de l\'individu', 'station ID', 'Nom de la station', 'Description de la station', 'Localité de la station', 'Habitat','Latitude', 'Longitude', 'Altitude', 'Code INSEE', 'Département', 'id de l\'espèce', 'Nom vernaculaire', 'Nom scientifique', 'Type', 'Plante / Animal']
        );
        // Lignes de data du csv
        foreach ($data as $ob){
            $obsDate = $ob['date'] ? $ob['date']->format('Y-m-d') : null;
            $missing = $ob['isMissing'] ? 'oui' : 'non';
            $createDate = $ob['createdAt'] ? $ob['createdAt']->format('Y-m-d') : null;
            $updateDate = $ob['updatedAt'] ? $ob['updatedAt']->format('Y-m-d') : null;
            $deletionDate = $ob['deletedAt'] ? $ob['deletedAt']->format('Y-m-d') : null;

            $list[] = [
                $ob['id'],
//                $ob['picture'],
                $obsDate,
                $missing,
                $ob['details'],
                $createDate,
                $updateDate,
                $deletionDate,
                $ob['individual']['id'],
                $ob['individual']['name'],
                $ob['individual']['details'],
                $ob['individual']['station']['id'],
                $ob['individual']['station']['name'],
                $ob['individual']['station']['description'],
                $ob['individual']['station']['locality'],
                $ob['individual']['station']['habitat'],
                $ob['individual']['station']['latitude'],
                $ob['individual']['station']['longitude'],
                $ob['individual']['station']['altitude'],
                $ob['individual']['station']['inseeCode'],
                $ob['individual']['station']['department'],
                $ob['individual']['species']['id'],
                $ob['individual']['species']['vernacular_name'],
                $ob['individual']['species']['scientific_name'],
                $ob['individual']['species']['type']['name'],
                $ob['individual']['species']['type']['reign'],
            ];
        }

        $fp = fopen('php://temp', 'w');
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        rewind($fp);
        $response = new Response(stream_get_contents($fp));
        fclose($fp);

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export_all_ods.csv"');

        return $response;
    }
}