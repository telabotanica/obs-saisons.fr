<?php

namespace App\Service;


use App\Entity\EventSpecies;

class DateService
{
	public function calculDateEvent(int $days)
	{
		$startDate = new \DateTime('01/01/2023');
		
		return $startDate->modify('+'.$days.' day');
	}
	
	public function calculCalendrierPheno(EventSpecies $stage){
		$stageCode = $stage->getEvent()->getStadeBbch();
		
		$p5 = $stage->getPercentile5();
		$p25 = $stage->getPercentile25();
		$p75 = $stage->getPercentile75();
		$p95 = $stage->getPercentile95();
		
		if ($p5){
			$p5 = $this->calculDateEvent($p5);
		}
		if ($p25){
			$p25 = $this->calculDateEvent($p25);
		}
		if ($p75){
			$p75 = $this->calculDateEvent($p75);
		}
		if ($p95){
			$p95 = $this->calculDateEvent($p95);
		}
		
		return [
			'name' => $stage->getEvent()->getName(),
			'bbch' => $stageCode,
			'p5' => $p5,
			'p25' => $p25,
			'p75' => $p75,
			'p95' => $p95,
		];
	}
}