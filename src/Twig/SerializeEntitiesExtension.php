<?php

namespace App\Twig;

use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Station;
use App\Entity\User;
use App\Service\EntityJsonSerialize;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class AppExtension.
 */
class SerializeEntitiesExtension extends AbstractExtension
{
    /**
     * @return array|TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('getJsonSerializedEditStation', [
                $this,
                'getJsonSerializedEditStation',
            ]),
            new TwigFilter('getJsonSerializedEditIndividual', [
                $this,
                'getJsonSerializedEditIndividual',
            ]),
            new TwigFilter('getJsonSerializedEditObservation', [
                $this,
                'getJsonSerializedEditObservation',
            ]),
            new TwigFilter('getJsonSerializedEditUserProfile', [
                $this,
                'getJsonSerializedEditUserProfile',
            ]),
        ];
    }

    public function getJsonSerializedEditStation(Station $station)
    {
        $entityJsonSerialize = new EntityJsonSerialize();

        return $entityJsonSerialize->getJsonSerializedEditStation($station);
    }

    public function getJsonSerializedEditIndividual(Individual $individual)
    {
        $entityJsonSerialize = new EntityJsonSerialize();

        return $entityJsonSerialize->getJsonSerializedEditIndividual($individual);
    }

    public function getJsonSerializedEditObservation(Observation $observation)
    {
        $entityJsonSerialize = new EntityJsonSerialize();

        return $entityJsonSerialize->getJsonSerializedEditObservation($observation);
    }

    public function getJsonSerializedEditUserProfile(User $user)
    {
        $entityJsonSerialize = new EntityJsonSerialize();

        return $entityJsonSerialize->getJsonSerializedEditUserProfile($user);
    }
}
