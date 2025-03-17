<?php

namespace App\Repository;

use App\Entity\FrenchRegions;
use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Station;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Observation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Observation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Observation[]    findAll()
 * @method Observation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ObservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Observation::class);
    }

    public function findAllObservationsInStation(Station $station, array $stationIndividuals = null): array
    {
        $stationIndividuals = $stationIndividuals ?? $this->getEntityManager()
            ->getRepository(Individual::class)
            ->findBy(['station' => $station])
        ;

        return $this->createQueryBuilder('o')
            ->where('o.individual IN (:individuals)')
            ->setParameter('individuals', $stationIndividuals)
            ->addOrderBy('o.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllObsContributorsCountInStation(array $stationIndividuals): int
    {
        $contributorsCount = count(
            $this->createQueryBuilder('o')
                ->where('o.individual IN (:individuals)')
                ->setParameter('individuals', $stationIndividuals)
                ->groupBy('o.user')
                ->getQuery()
                ->getResult()
        );

        // if the station exists there is at least one contributor
        if (0 >= $contributorsCount) {
            return 1;
        }

        return $contributorsCount;
    }

    public function findLastObs(int $limit): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.isMissing = 0')
            ->leftJoin('o.user', 'u')
            ->orderBy('o.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findLastUserObs(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->andWhere('o.isMissing = 0')
            ->setParameter('user', $user)
            ->orderBy('o.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findLastObsWithImages(int $limit): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.picture IS NOT NULL')
            ->andWhere('o.isMissing = 0')
            ->orderBy('o.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findObsCountThisYear(): int
    {
        $nowYear = date('Y');
        $allObsThisYear = $this->createQueryBuilder('o')
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
            ->innerJoin('i.user', 'u')
            ->where('YEAR(o.createdAt) = :nowYear')
            ->andWhere('o.deletedAt is null')
            ->andWhere('i.deletedAt is null')
            ->andWhere('s.deletedAt is null')
            ->andWhere('u.deletedAt is null')
            ->andWhere("u.email NOT IN ('admin@example.org','contact@obs-saisons.org')")
            ->setParameter('nowYear', $nowYear)
            ->getQuery()
            ->getResult()
        ;

        return count($allObsThisYear);
    }
	
	public function findObsCountPerYear(int $year): int
	{
		$allObsThisYear = $this->createQueryBuilder('o')
			->innerJoin('o.individual', 'i')
			->innerJoin('i.station', 's')
			->innerJoin('i.user', 'u')
			->where('YEAR(o.date) = :year')
			->andWhere('o.deletedAt is null')
			->andWhere('i.deletedAt is null')
			->andWhere('s.deletedAt is null')
			->andWhere('u.deletedAt is null')
			->andWhere("u.email NOT IN ('admin@example.org','contact@obs-saisons.org')")
			->setParameter('year', $year)
			->getQuery()
			->getResult()
		;
		
		return count($allObsThisYear);
	}
	
	public function findActiveMembersPerYear(int $year)
	{
		$qb = $this->createQueryBuilder('o')
			->innerJoin('o.user', 'u')
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
			->select('count(distinct u.id)')
			->where('YEAR(o.date) = :year')
            ->andWhere('o.deletedAt is null')
            ->andWhere('i.deletedAt is null')
            ->andWhere('s.deletedAt is null')
            ->andWhere('u.deletedAt is null')
            ->andWhere("u.email NOT IN ('admin@example.org','contact@obs-saisons.org')")
			->setParameter('year', $year)
			->getQuery()
			->getResult();
        
		return $qb[0][1] ?? 0;
	}

    public function findAllPublic(): array
    {
        return $this->createQueryBuilder('o')
            ->addSelect('PARTIAL o.{id, picture, isMissing, details, updatedAt, date, individual, event}')
            ->innerJoin('o.individual', 'i')
            ->addSelect('PARTIAL i.{id, name, species, station,details}')
            ->innerJoin('o.event', 'e')
            ->addSelect('PARTIAL e.{id, name, bbch_code,name,description}')
            ->innerJoin('i.species', 'sp')
            ->addSelect('PARTIAL sp.{id, vernacular_name, scientific_name}')
            ->innerJoin('sp.type', 'ts')
            ->addSelect('PARTIAL ts.{id, name, reign}')
            ->innerJoin('i.station', 'st')
            ->addSelect('PARTIAL st.{id, name, description,habitat,locality,latitude,longitude,altitude,inseeCode,department}')
//            ->where('st.isPrivate=0')
            ->Where('o.deletedAt is null')
			->andWhere('i.deletedAt is null')
			->andWhere('st.deletedAt is null')
            ->getQuery()
            ->getArrayResult();
    }

    public function findMinYear(): string
    {
        $minYear = '2006';

        $data = $this->createQueryBuilder('o')
            ->select('o.date as min')
            ->orderBy('o.date', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
        ;

        if ($data) {
            /** @var \DateTime $date */
            $date = $data[0]['min'];
            $minYear = $date->format('Y');
        }

        return $minYear;
    }
	
	public function findAllYears()
	{
		$qb = $this->createQueryBuilder('o')
			->select('distinct YEAR(o.date)')
			->orderBy('YEAR(o.date)', 'DESC')
			->getQuery()
			->getResult();
		
		$years = [];
		foreach ($qb as $year) {
			$years[] = $year[1];
		}
		
		return $years;
	}

    public function findByStationSlugForExport(string $stationSlug): array
    {
        $qb = $this->createQueryBuilder('o');

        return $qb
            ->innerJoin('o.individual', 'i')
            ->innerJoin('o.event', 'e')
            ->innerJoin('i.station', 'st')
            ->innerJoin('i.species', 'sp')
            ->innerJoin('sp.type', 'ts')
            ->addSelect(['i', 'e', 'st', 'sp', 'ts'])
            ->andWhere($qb->expr()->eq('st.slug', ':slug'))
            ->setParameter(':slug', $stationSlug)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findBySpeciesForExport (int $speciesId): array {
        $qb = $this->createQueryBuilder('o');

        return $qb
            ->innerJoin('o.individual', 'i')
            ->innerJoin('o.event', 'e')
            ->innerJoin('i.station', 'st')
            ->innerJoin('i.species', 'sp')
            ->innerJoin('sp.type', 'ts')
            ->addSelect(['i', 'e', 'st', 'sp', 'ts'])
//            ->andWhere($qb->expr()->eq('st.isPrivate', $qb->expr()->literal(false)))
            ->andWhere($qb->expr()->eq('sp.id', ':speciesId'))
            ->setParameter(':speciesId', $speciesId)
            ->getQuery()
            ->getResult()
            ;
    }

    public function createFilteredObservationListQueryBuilder(
        ?string $year,
        ?string $typeSpecies,
        ?string $species,
        ?string $event,
        ?string $department,
        ?string $region,
        ?string $station,
        ?string $individual,
        ?string $month,
        ?string $cumul
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('o')
            ->addSelect('PARTIAL o.{id, picture, isMissing, details, updatedAt, date, user, individual, event}')
            ->innerJoin('o.individual', 'i')
            ->addSelect('PARTIAL i.{id, name, species, station}')
            ->innerJoin('o.event', 'e')
            ->addSelect('PARTIAL e.{id, name, bbch_code}')
            ->innerJoin('o.user', 'u')
            ->addSelect('PARTIAL u.{id, displayName}')
            ->innerJoin('i.station', 'st')
            ->addSelect('PARTIAL st.{id, locality, inseeCode, habitat, latitude, town_latitude, longitude, town_longitude, altitude, slug, department,isPrivate}')
            ->innerJoin('i.species', 'sp')
            ->addSelect('PARTIAL sp.{id, vernacular_name, scientific_name}')
            ->innerJoin('sp.type', 'ts')
            ->addSelect('PARTIAL ts.{id, name, reign}')
        ;

        if ($year) {
            $qb->andWhere($qb->expr()->eq('YEAR(o.date)', ':year'))
                ->setParameter(':year', $year)
            ;
        }
        if (!empty($month) AND $month != 13) {
            if($cumul==="1"){
                $qb->andWhere('MONTH(o.date) BETWEEN 1 AND :month')
                    ->setParameter('month', $month);
            }else{
                $qb->andWhere($qb->expr()->eq('MONTH(o.date)', ':month'))
                    ->setParameter(':month', $month);
            }
            
        }
        if ($typeSpecies) {
            $qb->andWhere($qb->expr()->eq('ts.id', ':typeSpecies'))
                ->setParameter(':typeSpecies', $typeSpecies)
            ;
        }

        if ($species) {
            $qb->andWhere($qb->expr()->eq('sp.id', ':species'))
                ->setParameter(':species', $species)
            ;
        }

        if ($event) {
            $events = explode(',', $event);
            $qb->andWhere($qb->expr()->in('e.id', ':event'))
                ->setParameter(':event', $events)
            ;
        }

        if ($region) {
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('st.department', ':departments'))
                ->setParameter(':departments', $departments)
            ;
        } elseif ($department) {
            $qb->andWhere($qb->expr()->eq('st.department', ':department'))
                ->setParameter(':department', $department)
            ;
        }

        if ($station) {
            $qb->andWhere($qb->expr()->eq('st.slug', ':station'))
                ->setParameter(':station', $station)
            ;
        }

        if ($individual) {
            $qb->andWhere($qb->expr()->eq('i.name', ':individual'))
                ->setParameter(':individual', $individual)
            ;
        }

        // remove this line for real export:
        $qb->andWhere($qb->expr()->eq('o.isMissing', 0));

        $qb->orderBy('o.date', 'DESC');

        return $qb;
    }

    public function findFilteredForEventsEvolutionChart(
        string $species,
        string $event,
        ?string $region,
        ?string $department
    ) {
        $events = explode(',', $event);
        $qb = $this->createQueryBuilder('o');
        $qb
            ->innerJoin('o.individual', 'i')
            ->innerJoin('o.event', 'e')
            ->innerJoin('i.station', 'st')
            ->innerJoin('i.species', 'sp')
            ->select('YEAR(o.date) as year, AVG(DAYOFYEAR(o.date)) as dayOfYear')
            ->addSelect('MAKEDATE(YEAR(o.date), AVG(DAYOFYEAR(o.date))) as date, COUNT(o.date) as date_count')
            ->addSelect('CONCAT(e.name, \' \', e.bbch_code) as event')
            ->andWhere($qb->expr()->eq('sp.id', ':species'))
            ->setParameter(':species', $species)
            ->andWhere($qb->expr()->in('e.id', ':event'))
            ->setParameter(':event', $events)
            ->andWhere($qb->expr()->eq('o.isMissing', 0))
        ;

        if ($region) {
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('st.department', ':departments'))
                ->setParameter(':departments', $departments)
            ;
        } elseif ($department) {
            $qb->andWhere($qb->expr()->eq('st.department', ':department'))
                ->setParameter(':department', $department)
            ;
        }

        $qb
            ->addGroupBy('year')
            ->addGroupBy('event')
        ;

        return $qb
            ->getQuery()
            ->getScalarResult()
        ;
    }
	
	public function findOrderedObsPerUser(User $user){
		return $this->createQueryBuilder('o')
			->andWhere('o.user = :user')
			->setParameter('user', $user)
			->orderBy('o.date', 'DESC')
			->getQuery()
			->getResult()
			;
	}
	
	public function findOrderedObsPerUserForExport(User $user): array
	{
		$qb = $this->createQueryBuilder('o');
		
		return $qb
			->innerJoin('o.individual', 'i')
			->innerJoin('o.event', 'e')
			->innerJoin('i.station', 'st')
			->innerJoin('i.species', 'sp')
			->innerJoin('sp.type', 'ts')
			->addSelect(['i', 'e', 'st', 'sp', 'ts'])
			->andWhere('o.user = :user')
			->setParameter('user', $user)
			->orderBy('o.date', 'DESC')
			->getQuery()
			->getResult();
	}

    public function findTop10perType($reign, $year,$region=null): array {
        
        $qb = $this->createQueryBuilder('o')
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
            ->innerJoin('i.species', 'sp')
            ->innerJoin('sp.type','t')
            ->select('sp.scientific_name as scientific_name, sp.vernacular_name as vernacular_name, count(o) as obs')
            ->andWhere('o.deletedAt is null')
            ->andWhere("YEAR(o.date) = :year")
            ->andWhere("t.reign = :reign");

       if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('s.department', ':departments'))
                ->setParameter(':departments', $departments);
        }
        $qb->setParameter('year', $year)
           ->setParameter('reign', $reign)
           ->groupBy('sp.id')
           ->orderBy('count(o)', 'DESC')
           ->setMaxResults(10);

       return $qb->getQuery()->getResult();
    }

    public function findTop3Species(int $year, $region = null){
        $qb = $this->createQueryBuilder('o')
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
            ->innerJoin('i.species', 'sp')
            ->select('sp.scientific_name as scientific_name, sp.vernacular_name as vernacular_name, count(o) as obs')
            ->andWhere('o.deletedAt is null')
            ->andWhere("YEAR(o.date) = :year")
            ->setParameter('year',$year);

        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('s.department', ':departments'))
                ->setParameter(':departments', $departments);
        }

        $result = $qb
            ->groupBy('sp.id')
            ->orderBy('count(o)', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function countAllActiveStationsPerYear($year,$region=null){
        $qb = $this->createQueryBuilder('o')
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
            ->select(['YEAR(o.date) as year, COUNT(DISTINCT s) as Nb_stations_actives'])
            ->where("YEAR(o.date)=:year")
            ->setParameter("year",$year)
            ->andWhere('o.deletedAt IS NULL')
            ->andWhere('i.deletedAt IS NULL')
            ->andWhere('s.deletedAt IS NULL')
            ->andWhere('s.is_deactivated =0 OR s.is_deactivated is null')
            ->groupBy('year');
        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('s.department', ':departments'))
                ->setParameter(':departments', $departments);
        }
        return $qb
            ->getQuery()
            ->getResult();
    }

    public function countAllActiveCitiesPerYear($year,$region=null){
        $qb = $this->createQueryBuilder('o')
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
            ->select(['YEAR(o.date) as year, COUNT(DISTINCT s.inseeCode) as Nb_communes_actives'])
            ->where("YEAR(o.date)=:year")
            ->setParameter("year",$year)
            ->andWhere('o.deletedAt IS NULL')
            ->andWhere('i.deletedAt IS NULL')
            ->andWhere('s.deletedAt IS NULL')
            ->groupBy('year')
            ->orderBy('year', 'DESC');
        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('s.department', ':departments'))
                ->setParameter(':departments', $departments);
        }
        return $qb
            ->getQuery()
            ->getResult();
    }

    public function countStationsWithData(){
        $qb = $this->createQueryBuilder('o')
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
            ->select('COUNT(DISTINCT s)')
            ->andWhere('o.deletedAt IS NULL')
            ->andWhere('i.deletedAt IS NULL')
            ->andWhere('s.deletedAt IS NULL');

        $result = $qb
            ->getQuery()
            ->getResult();

        return $result[0][1] ?? 0;
    }

    public function top12UsersPerYear(int $year){
        $qb = $this->createQueryBuilder('o');

        return $qb
            ->innerJoin('o.user', 'u')
//            ->select('u.displayName as nom, count(o) as obs_total, count(o.deletedAt) as obs_deleted, count(o) - count(o.deletedAt) as obs_online')
            ->select('u.email as nom, count(o) as obs_total_online')
            ->where('YEAR(o.createdAt) = :year')
            ->setParameter('year', $year)
            ->groupBy('o.user')
            ->orderBy('count(o)', 'DESC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();
    }

    public function findObsAndUserPerYearPerRegion(int $year, $region = null): array
    {
        $allObsThisYear = $this->createQueryBuilder('o')
            ->join('o.individual', 'i')
            ->join('i.station', 's')
            ->join('i.user', 'u')
            ->select('COUNT(o.id) as nb_obs, COUNT(DISTINCT o.user) as nb_utilisateurs')
            ->where('YEAR(o.createdAt) = :year')
            ->andWhere('o.deletedAt is null')
            ->andWhere('i.deletedAt is null')
            ->andWhere('s.deletedAt is null')
            ->andWhere('u.deletedAt is null')
            ->setParameter('year', $year)
            ->andWhere("u.email NOT IN ('admin@example.org','contact@obs-saisons.org')");

        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $allObsThisYear->andWhere($allObsThisYear->expr()->in('s.department', ':departments'))
                ->setParameter(':departments', $departments);

        }

        $result = $allObsThisYear
            ->getQuery()
            ->getScalarResult()
        ;

        return $result;
    }

    public function findNewMembersPerYearPerRegion(int $year, $region=null)
    {
        $qb = $this->createQueryBuilder('o')
            ->join('o.individual', 'i')
            ->join('i.station', 's')
            ->join('i.user', 'u')
            ->select('COUNT(DISTINCT u)')
            ->where('YEAR(u.createdAt) = :year')
            ->andWhere('o.deletedAt is null')
            ->andWhere('i.deletedAt is null')
            ->andWhere('s.deletedAt is null')
            ->andWhere('u.deletedAt is null')
            ->andWhere("u.email NOT IN ('admin@example.org','contact@obs-saisons.org')")
            ->andWhere('u.status = 1')
            ->setParameter('year', $year)
            
        ;

        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('s.department', ':departments'))
                ->setParameter(':departments', $departments);
        }

        $result = $qb
            ->getQuery()
            ->getResult();

        return $result[0][1] ?? 0;
    }

    public function findactiveMembersPerYearPerRegion(int $year, $region = null)
    {
        $qb = $this->createQueryBuilder('o')
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
            ->innerJoin('o.user', 'u')
            ->select('count(o.id) as obs, count(DISTINCT u) as active_members, u.profileType as type')
            ->where('YEAR(o.date) = :year')
            ->andWhere('o.deletedAt is null')
            ->andWhere('i.deletedAt is null')
            ->andWhere('s.deletedAt is null')
            ->andWhere('u.deletedAt is null')
            ->andWhere("u.email NOT IN ('admin@example.org','contact@obs-saisons.org')")
            ->setParameter('year', $year)
            ->groupBy('u.profileType');

        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('s.department', ':departments'))
                ->setParameter(':departments', $departments);
        }

        $result = $qb
            ->getQuery()
            ->getResult();

        return $result[0]?? 0;
    }

    public function top5UsersPerYearPerRegion(int $year, $region = null){
        $qb = $this->createQueryBuilder('o')
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
            ->innerJoin('o.user', 'u')
            ->select('u.name as nom, u.postCode, count(o) as obs_total, count(o.deletedAt) as obs_deleted, count(o) - count(o.deletedAt) as obs_online')
            ->where('YEAR(o.date) = :year')
            ->andWhere('o.deletedAt is null')
            ->andWhere('i.deletedAt is null')
            ->andWhere('s.deletedAt is null')
            ->andWhere('u.deletedAt is null')
            ->andWhere("u.email NOT IN ('admin@example.org','contact@obs-saisons.org')")
            ->setParameter('year', $year)
            ->groupBy('o.user')
            ->orderBy('count(o)', 'DESC')
            ->setMaxResults(5);

        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('s.department', ':departments'))
                ->setParameter(':departments', $departments);
        }

        $result = $qb
            ->getQuery()
            ->getResult();

        return $result;
    }

    //Compte le nombre d'images pour la modération d'images
    public function countImages($selectedStatus, $selectedSpeciesId, $selectedUserId, $selectedEventId)
    {
        //Initialisation de la valeur pour éviter les erreurs
        $totalImagesQuery = 0;

        //try-catch pour la gestion d'erreur
        try{
            $totalImagesQuery = $this->createQueryBuilder('o')
                ->select('COUNT(o.id)')
                ->leftJoin('o.user', 'u')
                ->leftJoin('o.event', 'e')
                ->leftJoin('o.individual', 'i')
                ->leftJoin('i.species', 's');

            if ($selectedStatus !== '') {
                if ($selectedStatus == '0') {
                    $totalImagesQuery->where("(o.isPictureValid = :valid OR o.isPictureValid IS NULL) AND
                                            (o.picture IS NOT NULL AND o.picture NOT LIKE '/media%')")
                        ->setParameter('valid', 0);
                } else {
                    $totalImagesQuery->andWhere("(o.isPictureValid = :status )AND
                                            (o.picture IS NOT NULL AND o.picture NOT LIKE '/media%')")
                        ->setParameter('status', $selectedStatus);
                }
            } else {
                $totalImagesQuery->where("(o.isPictureValid = :valid OR o.isPictureValid IS NULL) AND
                                            (o.picture IS NOT NULL AND o.picture NOT LIKE '/media%')")
                    ->setParameter('valid', 0);
            }
            if (!empty($selectedSpeciesId)) {
                $totalImagesQuery->andWhere('s.id = :speciesId')
                    ->setParameter('speciesId', $selectedSpeciesId);
            }
            if (!empty($selectedUserId)) {
                $totalImagesQuery->andWhere('u.id = :userId')
                    ->setParameter('userId', $selectedUserId);
            }
            if (!empty($selectedEventId)) {
                $totalImagesQuery->andWhere('e.id = :eventId')
                    ->setParameter('eventId', $selectedEventId);
            }
        }catch (\Exception $exception){
            echo "An error occured -->" . $exception->getMessage();
        }


        //stockage du nombre d'images total
        return $totalImagesQuery->getQuery()->getSingleScalarResult();
    }

    //Prend toutes les images d'observation dans la bdd
    public function findImages($selectedStatus,
                               $selectedSpeciesId,
                               $selectedUserId,
                               $selectedEventId,
                               $offset,
                               $pageSize,
                               $sort)
    {
        $imagesQuery = '';
        // Requête pour récupérer les images avec les informations associées
        $imagesQuery = $this->createQueryBuilder('o')
            ->select('partial o.{id, createdAt, isPictureValid, picture, date}',
                'partial u.{id, name, email}',
                'partial e.{id, name}',
                'partial i.{id, name}',
                'partial s.{id, vernacular_name}')
            ->leftJoin('o.user', 'u')
            ->leftJoin('o.event', 'e')
            ->leftJoin('o.individual', 'i')
            ->leftJoin('i.species', 's');

        if ($sort === 'date_asc') {
            $imagesQuery->orderBy('o.date', 'ASC');
        } else {
            $imagesQuery->orderBy('o.date', 'DESC');
        }

        //Prise en compte de le requete de filtrage pas statut
        if ($selectedStatus !== '') {
            if ($selectedStatus == 0 ){
                $imagesQuery->where("(o.isPictureValid = :valid OR o.isPictureValid IS NULL) AND
                                            (o.picture IS NOT NULL AND o.picture NOT LIKE '/media%')")
                    ->setParameter('valid', 0);
            }else{
                $imagesQuery->where("
                (o.isPictureValid = :status AND o.picture IS NOT NULL) AND
                                            (o.picture IS NOT NULL AND o.picture NOT LIKE '/media%')")
                    ->setParameter('status', $selectedStatus);
            }
        } else {
            //cas par défault ou aucun statut n'est rentré en parametre
            $imagesQuery->where("(o.isPictureValid = :valid OR o.isPictureValid IS NULL) AND
                                           (o.picture IS NOT NULL AND o.picture NOT LIKE '/media%')")
                ->setParameter('valid', 0);
        }

        //Prise en compte des requetes de filtrage
        if (!empty($selectedSpeciesId)) {
            $imagesQuery->andWhere('s.id = :speciesId')
                ->setParameter('speciesId', $selectedSpeciesId);
        }
        if(!empty($selectedUserId)){
            $imagesQuery->andWhere('u.id = :userId')
                ->setParameter('userId', $selectedUserId);
        }
        if(!empty($selectedEventId)){
            $imagesQuery->andWhere('e.id = :eventId')
                ->setParameter('eventId', $selectedEventId);
        }

        $imagesQuery->setFirstResult($offset)->setMaxResults($pageSize);

       return $imagesQuery->getQuery()->getResult();
    }

    public function findImagesCarousel($species)
    {
        $imagesQuery = '';
        try{
            $imagesQuery = $this->createQueryBuilder('o')
                ->select(
                    'partial o.{id, picture, isPictureValid, updatedAt}',
                    'partial u.{id, name, displayName}',
                    'partial e.{id, name}',
                    'partial i.{id}'
                )
                ->leftJoin('o.user', 'u')
                ->leftJoin('o.event', 'e')
                ->leftJoin('o.individual', 'i')
                ->where("(o.isPictureValid = :valid AND i.species = :species) AND
                                            (o.picture IS NOT NULL AND o.picture NOT LIKE '/media%')")
                ->orderBy('o.createdAt', 'DESC')
                ->setMaxResults(10)
                ->setParameters([
                    'valid' => 1,
                    'species' => $species
                ]);
        }catch (\Exception $exception){
            echo 'An error occurred --> ' . $exception;
        }

// Execute the query to get the results
        return $imagesQuery->getQuery()->getResult();
    }

    public function countAllObservations($region)
    {
        $qb = $this->createQueryBuilder('o')
            ->select('count(o.id)')
            ->innerJoin('o.individual', 'i')
			->innerJoin('i.station', 's')
			->innerJoin('i.user', 'u')
            ->where('s.is_deactivated =0 OR s.is_deactivated is null');

        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('s.department', ':departments'))
                ->setParameter(':departments', $departments);
        }

        $result = $qb
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result;
    }

    public function countAllObservationsSince2015($region)
    {
        $qb = $this->createQueryBuilder('o')
            ->select('count(o.id)')
            ->innerJoin('o.individual', 'i')
			->innerJoin('i.station', 's')
			->innerJoin('i.user', 'u')
            ->where('s.is_deactivated =0 OR s.is_deactivated is null')
            ->andWhere('YEAR(o.date)>=2015');
        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('s.department', ':departments'))
                ->setParameter(':departments', $departments);
        }

        $result = $qb
            ->getQuery()
            ->getSingleScalarResult();
             
        return $result;
    }

    public function countAllObservationsInPacaFromJunetoJune($region)
    {

        $current_year=date('Y');
        $last_year=date('Y')-1;

        $qb = $this->createQueryBuilder('o')
            ->select('count(o.id)')
            ->innerJoin('o.individual', 'i')
			->innerJoin('i.station', 's')
			->innerJoin('i.user', 'u')
            ->where('s.is_deactivated =0 OR s.is_deactivated is null')
            ->andWhere("o.date >= CONCAT('$last_year','/06/01')")
            ->andWhere("o.date < CONCAT('$current_year','/07/01')");
        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('s.department', ':departments'))
                ->setParameter(':departments', $departments);
        }

        $result = $qb
            ->getQuery()
            ->getSingleScalarResult();
            
        return $result;
    }

    public function countAllObservationsCurrentYear($region)
    {

        $current_year=date('Y');
    
        $qb = $this->createQueryBuilder('o')
            ->select('count(o.id)')
            ->innerJoin('o.individual', 'i')
			->innerJoin('i.station', 's')
			->innerJoin('i.user', 'u')
            ->where('s.is_deactivated =0 OR s.is_deactivated is null')
            ->andWhere("YEAR(o.date) = $current_year");
        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $qb->andWhere($qb->expr()->in('s.department', ':departments'))
                ->setParameter(':departments', $departments);
        }

        $result = $qb
            ->getQuery()
            ->getSingleScalarResult();
            
        return $result;
    }

    public function countAllObservationsByDpt($department)
    {

        $qb = $this->createQueryBuilder('o')
            ->select('count(o.id)')
            ->innerJoin('o.individual', 'i')
			->innerJoin('i.station', 's')
			->innerJoin('i.user', 'u')
            ->where('s.is_deactivated =0 OR s.is_deactivated is null')
            ->andWhere('s.department = :department')
            ->setParameter(":department",$department);
        $result = $qb
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result;
    }

    public function countAllObservationsByDptSince2015($department)
    {
        $qb = $this->createQueryBuilder('o')
            ->select('count(o.id)')
            ->innerJoin('o.individual', 'i')
			->innerJoin('i.station', 's')
			->innerJoin('i.user', 'u')
            ->where('s.is_deactivated =0 OR s.is_deactivated is null')
            ->andWhere('YEAR(o.date)>=2015')
            ->andWhere('s.department = :department')
            ->setParameter(":department",$department);

        $result = $qb
            ->getQuery()
            ->getSingleScalarResult();
             
        return $result;
    }

    public function countAllObservationsByDptFromJunetoJune($department)
    {

        $current_year=date('Y');
        $last_year=date('Y')-1;

        $qb = $this->createQueryBuilder('o')
            ->select('count(o.id)')
            ->innerJoin('o.individual', 'i')
			->innerJoin('i.station', 's')
			->innerJoin('i.user', 'u')
            ->where('s.is_deactivated =0 OR s.is_deactivated is null')
            ->andWhere("o.date >= CONCAT('$last_year','/06/01')")
            ->andWhere("o.date < CONCAT('$current_year','/07/01')")
            ->andWhere('s.department = :department')
            ->setParameter(":department",$department);

        $result = $qb
            ->getQuery()
            ->getSingleScalarResult();
            
        return $result;
    }

    public function countAllObservationsByDptCurrentYear($department)
    {

        $current_year=date('Y');
        
        $qb = $this->createQueryBuilder('o')
            ->select('count(o.id)')
            ->innerJoin('o.individual', 'i')
			->innerJoin('i.station', 's')
			->innerJoin('i.user', 'u')
            ->where('s.is_deactivated =0 OR s.is_deactivated is null')
            ->andWhere("YEAR(o.date) = $current_year")
            ->andWhere('s.department = :department')
            ->setParameter(":department",$department);

        $result = $qb
            ->getQuery()
            ->getSingleScalarResult();
            
        return $result;
    }

    public function countAllObservators()
    {

        $qb = $this->createQueryBuilder('o')
            ->select('count(DISTINCT(o.user))');

        $result = $qb
            ->getQuery()
            ->getSingleScalarResult();
            
        return $result;
    }

    public function getObsForCharts($objet){
        
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT
                MONTH(o.date) AS mois,
                e.name AS etape,
                COUNT(o.id) AS nb_obs,
                (
                SELECT
                    COUNT(o3.id)
                FROM
                    observation o3
                INNER JOIN event e3 ON
                    o3.event_id = e3.id
                INNER JOIN individual i3 ON o3.individual_id = i3.id
                INNER JOIN species sp3 ON i3.species_id = sp3.id
                INNER JOIN station s3 ON i3.station_id=s3.id
                WHERE
                    o3.is_missing = 1 AND o3.deleted_at IS NULL AND MONTH(o3.date) = mois AND e3.name = etape".$this->setParams($objet,'3').
                "GROUP BY
                    MONTH(o3.date),
                    e3.name
            ) AS nb_obs_manquantes,
            (
                SELECT
                    COUNT(o2.id)
                FROM
                    observation o2
                INNER JOIN event e2 ON
                    o2.event_id = e2.id
                INNER JOIN individual i2 ON o2.individual_id = i2.id
                INNER JOIN species sp2 ON i2.species_id = sp2.id
                INNER JOIN station s2 ON i2.station_id=s2.id
                WHERE
                    o2.deleted_at IS NULL ".$this->setParams($objet,'2').
            ") AS nb_obs_total
            FROM
                observation o
            INNER JOIN event e ON
                o.event_id = e.id
            INNER JOIN individual i ON o.individual_id = i.id
            INNER JOIN species sp ON i.species_id = sp.id
            INNER JOIN station s ON i.station_id=s.id
            WHERE
                o.deleted_at IS NULL AND s.deleted_at IS NULL".$this->setParams($objet,'').
            "GROUP BY
                mois,
                etape ORDER BY mois";
        
        $stmt = $conn->prepare($sql);
        $results = $stmt->executeQuery()->fetchAllAssociative();
        return $results;
    }

    public function setParams($objet,$numero){
        $params='';
        if(!empty($objet)){
            if (!empty($objet->year) AND preg_match('~\b\d{4}\b\+?~',$objet->year)){
                $year=$objet->year;
                $params.=" AND YEAR(o$numero.date)=$year";
            }
            if (!empty($objet->region) AND preg_match('^\d+$^',$objet->region) AND intval($objet->region)<14){
                $region=$objet->region;
                $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
                $str_dpts="";
                foreach ($departments as $dpt){
                    $str_dpts.="'".$dpt."',";
                }
                $str_dpts=rtrim($str_dpts,",");
                $params.=" AND s$numero.department IN ($str_dpts)";
            }
            if (!empty($objet->dpt) AND ((preg_match('^\d+$^',$objet->dpt) AND intval($objet->dpt) < 99) OR preg_match('^\dA$^',$objet->dpt) OR preg_match('^\dB$^',$objet->dpt))){
                $dpt=$objet->dpt;
                $params.=" AND s$numero.department = '$dpt'";
            }
            if (!empty($objet->specy) AND preg_match('^\d+$^',$objet->specy)){
                $specy=$objet->specy;
                $params.=" AND sp$numero.id = $specy";
            }
        }
        
        return "$params ";
    }

    public function findObservationsGraph($selectedSpeciesIds, $selectedEventIds, $selectedYears)
    {
        $observationQuery = '';
        $containsValue = false;
        try {
            $observationQuery = $this->createQueryBuilder('o')
            ->select(
                    'partial o.{id, date}',
                    'partial e.{id, name, bbch_code}',
                    'partial i.{id}',
                    'partial s.{id, vernacular_name}'
                )
                ->leftJoin('o.event', 'e')
                ->leftJoin('o.individual', 'i')
                ->leftJoin('i.species', 's');
                
                for ($i = 0; $i < count($selectedSpeciesIds); $i++) {
                    if ($selectedSpeciesIds[$i] != '') {
                        $containsValue = true;
                        break;
                    }
                }
                
                if ($containsValue){
                    $observationQuery
                    ->andWhere('s.id IN (:speciesIds)')
                    ->setParameter('speciesIds', array_unique($selectedSpeciesIds));
                }
              
                if (!empty($selectedEventIds)) {
                    $observationQuery
                    ->andWhere('e.id IN (:eventIds)')
                    ->setParameter('eventIds', array_unique($selectedEventIds));
                }
                if (!empty($selectedYears)) {
                    $observationQuery
                    ->andWhere('YEAR(o.date) IN (:years)')
                    ->setParameter('years', array_unique($selectedYears));
                }
                
        } catch (\Exception $exception) {
            echo 'An error occurred --> ' . $exception->getMessage();
        }
       
        return $observationQuery->getQuery()->getResult();
    }


}
