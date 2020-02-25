<?php

namespace App\DataFixtures;

use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class EventFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $eventsData = json_decode(file_get_contents('src/Ressources/ods_events.json'));

        foreach ($eventsData as $i => $eventData) {
            $event = new Event();
            $event->setStadeBbch($eventData->stade_bbch);
            $event->setName($eventData->name);
            $event->setDescription($eventData->description);
            $event->setIsObservable($eventData->is_observable);

            $manager->persist($event);

            $this->addReference(sprintf('event-%d', $i + 1), $event);
        }

        $manager->flush();
    }
}
