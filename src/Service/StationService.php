<?php

namespace App\Service;

use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Station;
use Doctrine\ORM\EntityManagerInterface;

class StationService {
	private EntityManagerInterface $em;
	
	public function __construct(EntityManagerInterface $em)
	{
		$this->em = $em;
	}
	public function countContributors(Station $station){
		$observationsRepository = $this->em->getRepository(Observation::class);
		$individualsRepository = $this->em->getRepository(Individual::class);
		
		$individuals = $individualsRepository->findAllIndividualsInStation($station);
		$contributorsCount = $observationsRepository->findAllObsContributorsCountInStation($individuals);
		
		return $contributorsCount;
	}
	
}