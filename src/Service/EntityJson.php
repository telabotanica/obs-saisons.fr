<?php

namespace App\Service;

use App\Entity\Individual;
use App\Entity\Observation;
use App\Entity\Station;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class EntityJson extends EntityJsonSerialize
{
    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function getJsonEditStation(Station $station)
    {
        $stationArray = $this->manager->getRepository(Station::class)->findStationEditArray($station);

        return json_encode($stationArray);
    }

    public function getJsonEditIndividual(Individual $individual)
    {
        return json_encode($this->individualCallBack($individual));
    }

    public function getJsonEditObservation(Observation $observation)
    {
        $observationArray = [
            'id' => $observation->getId(),
            'picture' => $observation->getPicture(),
            'isMissing' => $observation->getIsMissing(),
            'details' => $observation->getDetails(),
            'date' => $this->dateCallback($observation->getDate()),
            'event' => $this->entityObjectIdCallback($observation->getEvent()),
            'individual' => $this->individualCallBack($observation->getIndividual()),
            'user' => $this->userCallBack($observation->getUser()),
        ];

        return json_encode($observationArray);
    }

    public function getJsonEditUserProfile(User $user)
    {
        $userProfileArray = [
            'id' => $user->getId(),
            'displayName' => $user->getDisplayName(),
            'name' => $user->getName(),
            'avatar' => $user->getAvatar(),
            'locality' => $user->getLocality(),
            'country' => $user->getCountry(),
            'postCode' => $user->getPostCode(),
            'profileType' => $user->getProfileType(),
            'isNewsletterSubscriber' => $user->getIsNewsletterSubscriber(),
            'isMailsSubscriber' => $user->getIsMailsSubscriber(),
        ];

        return json_encode($userProfileArray);
    }
}
