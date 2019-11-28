<?php

namespace App\DataFixtures;

use App\Service\PostsGenerator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class PostFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $generator = new PostsGenerator();

        foreach ($generator->getArticles() as $article) {
            $manager->persist($article);
        }

        foreach ($generator->getEvents() as $event) {
            $manager->persist($event);
        }

        $manager->flush();
    }
}
