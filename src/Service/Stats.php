<?php

namespace App\Service;

use App\Entity\EventSpecies;
use App\Entity\Observation;
use App\Entity\Station;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class Stats
{
    private $manager;

     /**
     * @param $manager
     */
     public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function getStats(int $year){
         $result = [];

        //Nbr d'obs dans l'année
        $result['obsPerYear'] = $this->manager->getRepository(Observation::class)->findObsCountPerYear($year);
        $result['nbStations'] = $this->manager->getRepository(Station::class)->countStationsEachYear($year);
        //Nbr de nouvels inscrits dans l'année
        $result['newMembers'] = $this->manager->getRepository(User::class)->findNewMembersPerYear($year);
        //Nbre de membres actifs dans l'année
        $result['activeMembers'] = $this->manager->getRepository(Observation::class)->findActiveMembersPerYear($year);
        // Nbre de stations avec au moins 1 donnée ($nbStationsWithData[0])
        $result['nbStationsWithData'] = $this->manager->getRepository(Observation::class)->countStationsWithData();

        // Top 10 obs par type
        $result['top10Animal'] = $this->manager->getRepository(Observation::class)->findTop10perType('animaux', $year);
        $result['top10Plantes'] = $this->manager->getRepository(Observation::class)->findTop10perType('plantes', $year);
        $result['top10Champignons'] = $this->manager->getRepository(Observation::class)->findTop10perType('champignons', $year);

        // Top 3 espèce
        $result['top3Species'] = $this->manager->getRepository(Observation::class)->findTop3Species($year);

        // Array du nombre de users par status
        $result['membersPerStatus'] = $this->manager->getRepository(User::class)->findTotalMembersPerStatus();
        // Array des stations actives par année
        $result['activeStationsPerYear'] = $this->manager->getRepository(Observation::class)->countAllActiveStationsPerYear();
        // Array des communes actives par année
        $result['activeCitiesPerYear'] = $this->manager->getRepository(Observation::class)->countAllActiveCitiesPerYear();

        // Top 12 utilisateurs
        $result['top12Users'] = $this->manager->getRepository(Observation::class)->top12UsersPerYear($year);

        // Par Région
        //Nbr d'obs et de users inscrits dans l'année en Occitanie
        $result['occitanie']['obsPerYear'] = $this->manager->getRepository(Observation::class)->findObsAndUserPerYearPerRegion($year, 12);
        //Nbr d'obs et de users inscrits dans l'année en Provence
        $result['provence']['obsPerYear'] = $this->manager->getRepository(Observation::class)->findObsAndUserPerYearPerRegion($year, 13);
        // Top 5 users Occitanie
        $result['occitanie']['top5Users'] = $this->manager->getRepository(Observation::class)->top5UsersPerYearPerRegion($year, 12);
        // Top 5 users Provence
        $result['provence']['top5Users'] = $this->manager->getRepository(Observation::class)->top5UsersPerYearPerRegion($year, 13);
        // Nbre de stations Occitanie
        $result['occitanie']['nbStations'] = $this->manager->getRepository(Station::class)->countStationsEachYearPerRegion($year, 12);
        // Nbre de stations Provence
        $result['provence']['nbStations'] = $this->manager->getRepository(Station::class)->countStationsEachYearPerRegion($year, 13);

        $result['provence']['allStations'] = $this->manager->getRepository(Station::class)->countAllStationsInPaca(13);
        
        $result['provence']['allStations2015'] = $this->manager->getRepository(Station::class)->countAllStationsInPacaSince2015(13);

        $result['provence']['allStationsJune'] = $this->manager->getRepository(Station::class)->countAllStationsInPacaFromJunetoJune(13);
        //nbre users actifs par type en occitanie
        $result['occitanie']['activeMembers'] = $this->manager->getRepository(Observation::class)->findactiveMembersPerYearPerRegion($year, 12);
        //nbre users actifs par type en PProvence
        $result['provence']['activeMembers'] = $this->manager->getRepository(Observation::class)->findactiveMembersPerYearPerRegion($year, 13);
        //Nbr de nouvels inscrits dans l'année en Occitanie
        $result['occitanie']['newMembers'] = $this->manager->getRepository(Observation::class)->findNewMembersPerYearPerRegion($year, 12);
        //Nbr de nouvels inscrits dans l'année en Provence
        $result['provence']['newMembers'] = $this->manager->getRepository(Observation::class)->findNewMembersPerYearPerRegion($year, 13);
        $result['occitanie']['top3'] = $this->manager->getRepository(Observation::class)->findTop3Species($year, 12);
        $result['provence']['top3'] = $this->manager->getRepository(Observation::class)->findTop3Species($year, 13);

        return$result;
    }
}