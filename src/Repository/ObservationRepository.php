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
            ->where('YEAR(o.createdAt) = :nowYear')
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
			->where('YEAR(o.createdAt) = :year')
			->andWhere('o.deletedAt is null')
			->andWhere('i.deletedAt is null')
			->andWhere('s.deletedAt is null')
			->andWhere('u.deletedAt is null')
			->andWhere('u.roles NOT LIKE :role')
			->setParameter('year', $year)
			->setParameter('role','%ROLE_ADMIN')
			->getQuery()
			->getResult()
		;
		
		return count($allObsThisYear);
	}
	
	public function findActiveMembersPerYear(int $year)
	{
		$qb = $this->createQueryBuilder('o')
			->innerJoin('o.user', 'u')
			->select('distinct u.id')
			->where('YEAR(o.createdAt) = :year')
			->andWhere('u.roles NOT LIKE :role')
			->setParameter('year', $year)
			->setParameter('role','%ROLE_ADMIN')
			->getQuery()
			->getResult()
		;
		return count($qb);
	}

    public function findAllPublic(): array
    {
        $qb = $this->createQueryBuilder('o');

        return $qb
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
//            ->andWhere($qb->expr()->eq('s.isPrivate', $qb->expr()->literal(false)))
            ->addSelect(['s', 'i'])
            ->innerJoin('i.species', 'sp')
            ->addSelect('PARTIAL sp.{id, vernacular_name, scientific_name}')
            ->innerJoin('sp.type', 'ts')
            ->addSelect('PARTIAL ts.{id, name, reign}')
            ->innerJoin('o.event', 'e')
            ->addSelect('PARTIAL e.{id, bbch_code, name, description}')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getArrayResult()
        ;
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
        ?string $individual
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
            ->addSelect('PARTIAL st.{id, locality, inseeCode, habitat, latitude, longitude, altitude, slug, department}')
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
			->getResult()
			;
	}

    public function findTop10perType($reign, $year): array {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery('
           SELECT s.scientific_name, s.vernacular_name, count(o) as nb_obs
           FROM App\Entity\Observation o
           JOIN o.individual i
           JOIN i.species s
           JOIN s.type ts
           JOIN o.user u
           WHERE o.deletedAt IS NULL
             AND ts.reign = :reign
             AND YEAR(o.createdAt) = :year
           GROUP BY s.id
           ORDER BY count(o) DESC 
       ');
       $query->setParameter('year', $year)
           ->setParameter('reign', $reign)
           ->setMaxResults(10);

       return $query->getResult();
    }

    public function findTop3Species(int $year, $region = null){
        $qb = $this->createQueryBuilder('o')
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
            ->innerJoin('o.user', 'u')
            ->innerJoin('i.species', 'sp')
            ->innerJoin('sp.type', 'ts')
            ->select('sp.scientific_name as scientific_name, sp.vernacular_name as vernacular_name, count(o) as obs')
            ->andWhere('o.deletedAt is null')
            ->andWhere('YEAR(o.createdAt) >= 2015');

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

    public function countAllActiveStationsPerYear(){
        $qb = $this->createQueryBuilder('o');

        return $qb
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
            ->select(['YEAR(o.createdAt) as year, COUNT(DISTINCT s) as Nb_stations_actives'])
            ->andWhere('o.deletedAt IS NULL')
            ->andWhere('i.deletedAt IS NULL')
            ->andWhere('s.deletedAt IS NULL')
            ->groupBy('year')
            ->orderBy('year', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countAllActiveCitiesPerYear(){
        $qb = $this->createQueryBuilder('o');

        return $qb
            ->innerJoin('o.individual', 'i')
            ->innerJoin('i.station', 's')
            ->select(['YEAR(o.createdAt) as year, COUNT(DISTINCT s.inseeCode) as Nb_communes_actives'])
            ->andWhere('o.deletedAt IS NULL')
            ->andWhere('i.deletedAt IS NULL')
            ->andWhere('s.deletedAt IS NULL')
            ->groupBy('year')
            ->orderBy('year', 'DESC')
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
            ->select('u.email, u.displayName, count(o) as obs_total, count(o.deletedAt) as obs_deleted, count(o) - count(o.deletedAt) as obs_online')
            ->where('YEAR(o.createdAt) = :year')
            ->setParameter('year', $year)
            ->groupBy('o.user')
            ->orderBy('count(o)', 'DESC')
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
            ->andWhere('u.roles NOT LIKE :role')
            ->setParameter('year', $year)
            ->setParameter('role','%ROLE_ADMIN%');

        if ($region){
            $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
            $allObsThisYear->andWhere($allObsThisYear->expr()->in('s.department', ':departments'))
                ->setParameter(':departments', $departments);

        }

        $result = $allObsThisYear
            ->getQuery()
            ->getResult()
        ;

        return $result[0];
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
            ->andWhere('u.roles NOT LIKE :role')
            ->andWhere('u.status = 1')
            ->setParameter('year', $year)
            ->setParameter('role','%ROLE_ADMIN')
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
            ->where('YEAR(o.createdAt) = :year')
            ->andWhere('o.deletedAt is null')
            ->andWhere('i.deletedAt is null')
            ->andWhere('s.deletedAt is null')
            ->andWhere('u.deletedAt is null')
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
            ->select('u.email, u.displayName, u.postCode, count(o) as obs_total, count(o.deletedAt) as obs_deleted, count(o) - count(o.deletedAt) as obs_online')
            ->where('YEAR(o.createdAt) = :year')
            ->andWhere('o.deletedAt is null')
            ->andWhere('i.deletedAt is null')
            ->andWhere('s.deletedAt is null')
            ->andWhere('u.deletedAt is null')
            ->setParameter('year', $year)
            ->groupBy('o.user')
            ->orderBy('count(o)', 'DESC');

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

    // /**
    //  * @return Observation[] Returns an array of Observation objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Observation
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

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
                    $totalImagesQuery->where("(o.is_picture_valid = :valid OR o.is_picture_valid IS NULL) AND
                                            (o.picture IS NOT NULL AND o.picture NOT LIKE '/media%')")
                        ->setParameter('valid', 0);
                } else {
                    $totalImagesQuery->andWhere("(o.is_picture_valid = :status )AND
                                            (o.picture IS NOT NULL AND o.picture NOT LIKE '/media%')")
                        ->setParameter('status', $selectedStatus);
                }
            } else {
                $totalImagesQuery->where("(o.is_picture_valid = :valid OR o.is_picture_valid IS NULL) AND
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
    public function findImages($selectedStatus, $selectedSpeciesId, $selectedUserId, $selectedEventId, $offset, $pageSize)
    {
        $imagesQuery = '';
        // Requête pour récupérer les images avec les informations associées
        $imagesQuery = $this->createQueryBuilder('o')
            ->select('partial o.{id, createdAt, is_picture_valid, picture, date}',
                'partial u.{id, name}',
                'partial e.{id, name}',
                'partial i.{id, name}',
                'partial s.{id, vernacular_name}')
            ->leftJoin('o.user', 'u')
            ->leftJoin('o.event', 'e')
            ->leftJoin('o.individual', 'i')
            ->leftJoin('i.species', 's')
            ->orderBy('o.createdAt', 'ASC');

        //Prise en compte de le requete de filtrage pas statut
        if ($selectedStatus !== '') {
            if ($selectedStatus == 0 ){
                $imagesQuery->where("(o.is_picture_valid = :valid OR o.is_picture_valid IS NULL) AND
                                            (o.picture IS NOT NULL AND o.picture NOT LIKE '/media%')")
                    ->setParameter('valid', 0);
            }else{
                $imagesQuery->where("
                (o.is_picture_valid = :status AND o.picture IS NOT NULL) AND
                                            (o.picture IS NOT NULL AND o.picture NOT LIKE '/media%')")
                    ->setParameter('status', $selectedStatus);
            }
        } else {
            //cas par défault ou aucun statut n'est rentré en parametre
            $imagesQuery->where("(o.is_picture_valid = :valid OR o.is_picture_valid IS NULL) AND
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
                    'partial o.{id, picture, is_picture_valid, updatedAt}',
                    'partial u.{id, name}',
                    'partial e.{id, name}',
                    'partial i.{id}'
                )
                ->leftJoin('o.user', 'u')
                ->leftJoin('o.event', 'e')
                ->leftJoin('o.individual', 'i')
                ->where("(o.is_picture_valid = :valid AND i.species = :species) AND
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
}
