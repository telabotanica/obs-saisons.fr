<?php

namespace App\Twig;

use App\Entity\EventSpecies;
use App\Entity\Individual;
use App\Entity\Observation;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class AppExtension.
 */
class ObservationExtension extends AbstractExtension
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
            new TwigFunction('displayImageToObservation', [
                $this,
                'displayImageToObservation',
            ]),
            new TwigFunction('setObsChips', [
                $this,
                'setObsChips',
            ]),
        ];
    }

    public function displayImageToObservation(Observation $observation): string
    {
        if ($observation->getIsMissing()) {
            return '/media/layout/icons/eye-crossed.svg';
        } else {
            if ($observation->getPicture()) {
                return $observation->getPicture();
            } else {
                $pictureName = $observation->getIndividual()->getSpecies()->getPicture();
                if ('arbres' === $observation->getIndividual()->getSpecies()->getType()->getName()) {
                    $pictureName .= '_'.substr($observation->getEvent()->getStadeBbch(), 0, 1);
                }

                return '/media/species/'.$pictureName.'.jpg';
            }
        }
    }

    public function setObsChips(Individual $individual): array
    {
        $individualObservations = $this->em->getRepository(Observation::class)
            ->findBy(['individual' => $individual], ['date' => 'DESC'])
        ;

        $validEvents = [];
        $eventsForSpecies = $this->em->getRepository(EventSpecies::class)
            ->findBy(['species' => $individual->getSpecies()]);
        foreach ($eventsForSpecies as $eventSpecies) {
            $validEvents[] = $eventSpecies->getEvent();
        }

        $observationsPerYear = [];
        foreach ($individualObservations as $observation) {
            if (in_array($observation->getEvent(), $validEvents)) {
                $year = date_format($observation->getDate(), 'Y');
                $i = array_search(
                    $year,
                    array_column($observationsPerYear, 'year')
                );
                if (false === $i) {
                    $observationsPerYear[] = [
                        'year' => $year,
                        'observations' => [$observation],
                    ];
                } else {
                    $observationsPerYear[$i]['observations'][] = $observation;
                }
            }
        }

        return $observationsPerYear;
    }
}
