<?php

namespace App\Service;

use App\Entity\EventSpecies;
use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Species;
use App\Entity\Station;
use App\Entity\User;
use Closure;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class EntityJsonSerialize
{
    public function getJsonSerializedEditStation(Station $station)
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);

        $context = [
            AbstractNormalizer::IGNORED_ATTRIBUTES => [
                'user',
                'slug',
                'createdAt',
                'updatedAt',
                'deletedAt',
            ],
        ];

        return $serializer->serialize($station, 'json', $context);
    }

    public function getJsonSerializedEditIndividual(Individual $individual)
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);

        $context = [
            AbstractNormalizer::CALLBACKS => [
                'species' => Closure::fromCallable('self::entityObjectIdCallback'),
                'station' => Closure::fromCallable('self::entityObjectIdCallback'),
            ],
            AbstractNormalizer::ATTRIBUTES => [
                'id',
                'name',
                'species',
                'station',
            ],
        ];

        return $serializer->serialize($individual, 'json', $context);
    }

    public function getJsonSerializedEditObservation(Observation $observation)
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);

        $context = [
            AbstractNormalizer::CALLBACKS => [
                'user' => Closure::fromCallable('self::userCallBack'),
                'date' => Closure::fromCallable('self::dateCallback'),
                'event' => Closure::fromCallable('self::entityObjectIdCallback'),
                'individual' => Closure::fromCallable('self::individualCallBack'),
            ],
            AbstractNormalizer::IGNORED_ATTRIBUTES => [
                'createdAt',
                'updatedAt',
                'deletedAt',
            ],
        ];

        return $serializer->serialize($observation, 'json', $context);
    }

    public function getJsonSerializedEventSpeciesObservationType(EventSpecies $eventSpecies)
    {
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);

        $context = [
            AbstractNormalizer::CALLBACKS => [
                'species' => Closure::fromCallable('self::entityObjectIdCallback'),
                'event' => Closure::fromCallable('self::entityObjectIdCallback'),
                'aberrationStartDay' => Closure::fromCallable('self::aberrationDayCallback'),
                'aberrationEndDay' => Closure::fromCallable('self::aberrationDayCallback'),
            ],
            AbstractNormalizer::ATTRIBUTES => [
                'event',
                'species',
                'aberrationStartDay',
                'aberrationEndDay',
            ],
        ];

        return $serializer->serialize($eventSpecies, 'json', $context);
    }

    public function getJsonSerializedEditUserProfile(User $user)
    {

        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);

        $context = [
            AbstractNormalizer::ATTRIBUTES => [
                'id',
                'displayName',
                'name',
                'avatar',
                'locality',
                'country',
                'postCode',
                'profileType',
                'isNewsletterSubscriber',
                'isMailsSubscriber',
            ],
        ];

        return $serializer->serialize($user, 'json', $context);
    }

    public function entityObjectIdCallback($entityObject)
    {
        return ['id' => $entityObject->getId()];
    }

    public function userCallBack(User $user)
    {
        return [
            'id' => $user->getId(),
            'displayedName' => $user->getDisplayName(),
        ];
    }

    public function speciesCallBack(Species $species)
    {
        return [
            'id' => $species->getId(),
            'displayedName' => $species->getVernacularName(),
        ];
    }

    public function dateCallback(\DateTimeInterface $date)
    {
        return $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : null;
    }

    public function individualCallBack(Individual $individual)
    {
        return [
            'id' => $individual->getId(),
            'species' => $this->speciesCallBack($individual->getSpecies()),
            'station' => $this->entityObjectIdCallback($individual->getStation()),
        ];
    }

    public function aberrationDayCallback(?string $aberrationDay)
    {
        $date = null;
        $displayedDate = null;
        if ($aberrationDay) {
            $transDateTime = new HandleDateTime();
            $startDateTime = date_create_from_format('z', $aberrationDay);
            $date = date_format($startDateTime, 'm-d');
            $displayedDate = $transDateTime->dateTransFormat('d MMMM', $startDateTime);
        }

        return ['date' => $date, 'displayedDate' => $displayedDate];
    }
}
