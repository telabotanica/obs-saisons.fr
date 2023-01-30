<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;

class CsvService
{
    public function createCsv(array $list, string $fileName): Response
    {
        $fp = fopen('php://temp', 'w');
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }

        rewind($fp);
        $response = new Response(stream_get_contents($fp));
        fclose($fp);

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$fileName.'.csv');

        return $response;
    }
    public function exportCsvAll(array $data): Response
    {
        $list = [
            // Colonnes titre du csv
            ['Id','Date', 'IsMissing', 'Détails', 'Date de l\'observation', 'Mise à jour', 'Date de suppression', 'id de l\'individu', 'Nom de l\'individu', 'Détail de l\'individu', 'station ID', 'Nom de la station', 'Description de la station', 'Localité de la station', 'Habitat','Latitude', 'Longitude', 'Altitude', 'Code INSEE', 'Département', 'id de l\'espèce', 'Nom vernaculaire', 'Nom scientifique', 'Type', 'Plante / Animal']
        ];
        // Lignes de data du csv
        foreach ($data as $ob){
            $obsDate = $ob['date'] ? $ob['date']->format('Y-m-d') : null;
            $missing = $ob['isMissing'] ? 'oui' : 'non';
            $createDate = $ob['createdAt'] ? $ob['createdAt']->format('Y-m-d') : null;
            $updateDate = $ob['updatedAt'] ? $ob['updatedAt']->format('Y-m-d') : null;
            $deletionDate = $ob['deletedAt'] ? $ob['deletedAt']->format('Y-m-d') : null;

            $list[] = [
                $ob['id'],
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

        return $this->createCsv($list, 'export_all_ods');
    }
    public function exportCsvStation(array $data, string $slug): Response
    {
        $list = [
            // Colonnes titre du csv
            [
                'Id', 'Date', 'IsMissing', 'Détails', 'Date de l\'observation', 'Mise à jour', 'Date de suppression', 'Utilisateur', 'id de l\'individu', 'Nom de l\'individu', 'Détail de l\'individu', 'station ID', 'Station public / privé', 'Nom de la station', 'Description de la station', 'Localité de la station', 'Habitat','Latitude', 'Longitude', 'Altitude', 'Code INSEE', 'Département', 'id de l\'espèce', 'Nom vernaculaire', 'Nom scientifique', 'Description de l\'espèce ', 'Type', 'Plante / Animal', 'Stade phénologique', 'Description du stade phénologique', 'Stade phénologique observé',
            ]
        ];
        // Lignes de data du csv
        foreach ($data as $ob){
            $obsDate = $ob->getDate() ? $ob->getDate()->format('Y-m-d') : null;
            $missing = $ob->getIsMissing() ? 'oui' : 'non';
            $createDate = $ob->getCreatedAt() ? $ob->getCreatedAt()->format('Y-m-d') : null;
            $updateDate = $ob->getUpdatedAt() ? $ob->getUpdatedAt()->format('Y-m-d') : null;
            $deletionDate = $ob->getDeletedAt() ? $ob->getDeletedAt()->format('Y-m-d') : null;
            $isPrivate = $ob->getIndividual()->getStation()->getIsPrivate() ? 'oui' : 'non';
            $isObservable = $ob->getEvent()->getIsObservable() ? 'oui' : 'non';

            $list[] = [
                $ob->getId(),
                $obsDate,
                $missing,
                $ob->getDetails(),
                $createDate,
                $updateDate,
                $deletionDate,
                $ob->getUser()->getDisplayName(),
                $ob->getIndividual()->getId(),
                $ob->getIndividual()->getName(),
                $ob->getIndividual()->getDetails(),
                $ob->getIndividual()->getStation()->getId(),
                $isPrivate,
                $ob->getIndividual()->getStation()->getName(),
                $ob->getIndividual()->getStation()->getDescription(),
                $ob->getIndividual()->getStation()->getLocality(),
                $ob->getIndividual()->getStation()->getHabitat(),
                $ob->getIndividual()->getStation()->getLatitude(),
                $ob->getIndividual()->getStation()->getLongitude(),
                $ob->getIndividual()->getStation()->getAltitude(),
                $ob->getIndividual()->getStation()->getInseeCode(),
                $ob->getIndividual()->getStation()->getDepartment(),
                $ob->getIndividual()->getSpecies()->getId(),
                $ob->getIndividual()->getSpecies()->getVernacularName(),
                $ob->getIndividual()->getSpecies()->getScientificName(),
                $ob->getIndividual()->getSpecies()->getDescription(),
                $ob->getIndividual()->getSpecies()->getType()->getName(),
                $ob->getIndividual()->getSpecies()->getType()->getReign(),
                $ob->getEvent()->getName(),
                $ob->getEvent()->getDescription(),
                $isObservable
            ];
        }

        return $this->createCsv($list, 'export_'.$slug);
    }

    public function exportCsvSpecies(array $data): Response
    {
        $list = [
            // Colonnes titre du csv
            [
                'Id', 'Nom vernaculaire', 'Nom scientifique', 'Description', 'Image'
            ]
        ];
        // Lignes de data du csv
        foreach ($data as $specie){

            $list[] = [
                $specie['id'],
                $specie['vernacular_name'],
                $specie['scientific_name'],
                $specie['description'],
                $specie['picture'],
            ];
        }

        return $this->createCsv($list, 'export_species');
    }

    public function exportCsvEvents(array $data, string $fileName): Response
    {
        $list = [
            // Colonnes titre du csv
            [
                'Id', 'code bbch', 'Nom', 'Description'
            ]
        ];
        // Lignes de data du csv
        foreach ($data as $event){
            $list[] = [
                $event['id'],
                $event['bbch_code'],
                $event['name'],
                $event['description'],
            ];
        }

        return $this->createCsv($list, $fileName);
    }
}