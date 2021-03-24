<?php

namespace App\Twig;

use App\Entity\EventSpecies;
use App\Entity\Species;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class AppExtension.
 */
class EventSpeciesExtension extends AbstractExtension
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('getEventsSpeciesForSpecies', [
                $this,
                'getEventsSpeciesForSpecies',
            ]),
        ];
    }

    public function getEventsSpeciesForSpecies(Species $species): array
    {
        return $this->em->getRepository(EventSpecies::class)
            ->findBy(['species' => $species])
        ;
    }
}
