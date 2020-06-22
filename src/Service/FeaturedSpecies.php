<?php

namespace App\Service;

use App\Entity\EventSpecies;
use Doctrine\ORM\EntityManagerInterface;

class FeaturedSpecies
{
    private $em;

    private $now;

    private $featured;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->now = date('z');
        $this->featured = [];
    }

    public function getRandomFeaturedSpecies()
    {
        $featured = $this->getFeaturedSpecies();

        return array_rand($featured);
    }

    public function getShuffledFeaturedSpecies()
    {
        $featured = $this->getFeaturedSpecies();

        return shuffle($featured);
    }

    /**
     * Returns EventSpecies collection filtered by featured date.
     *
     * @return EventSpecies[]
     */
    public function getFeaturedSpecies()
    {
        $featuredEventSpecies = $this->em->getRepository(EventSpecies::class)->findFeatured();

        /**
         * @var $eventSpecies EventSpecies
         */
        foreach ($featuredEventSpecies as $eventSpecies) {
            $now = $this->now;
            $end = $eventSpecies->getFeaturedEndDay();
            $start = $eventSpecies->getFeaturedStartDay();

            if ($end < 1) {
                $end += 365;
            }

            if ($eventSpecies->getFeaturedEndDay() < $eventSpecies->getFeaturedStartDay()) {
                $end += 365;

                if ($this->now < $eventSpecies->getFeaturedEndDay()) {
                    $now += 365;
                }
            }

            if ($now >= $start && $now <= $end) {
                $this->featured[] = $eventSpecies;
            }
        }

        return $this->featured;
    }
}
