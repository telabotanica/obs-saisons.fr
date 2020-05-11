<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Service\SlugGenerator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker;

class PostFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $articles = [];
        $events = [];

        $faker = Faker\Factory::create('fr_FR');

        $slugGenerator = new SlugGenerator();

        for ($i = 0; $i < 27; ++$i) {
            $title = substr($faker->sentence(6, true),0,-1);
            $dateCreatedAt = $faker->dateTimeBetween('-10 years', 'now', 'Europe/Paris');
            $cover = '/media/layout/image_station.png';
            $content = '
                <blockquote>'.$faker->sentence(6, true).'</blockquote>
                <figure>
                    <img src="'.$cover.'" alt="">
                    <figcaption>'.$faker->sentence(4, true).'</figcaption>
                </figure>
                <h3>'.substr($faker->sentence(3, true),0,-1).'</h3>
                <p>'.$faker->paragraph(3, true).' <strong>'.substr($faker->sentence(2, false),0,-1).'</strong> '.$faker->sentence(5, true).' <a href="">'.substr($faker->sentence(3, false),0,-1).'</a> '.$faker->paragraph(3, true).'</p>
                <p>'.$faker->paragraph(5, true).' <a href="">'.substr($faker->sentence(3, false),0,-1).'</a>, '.$faker->paragraph(3, true).'</p>
            ';

            $article = new Post();
            $article->setCategory('article');
            $article->setCreatedAt($dateCreatedAt);
            $article->setContent($content);
            $article->setTitle($title);
            $article->setAuthor($this->getReference('user-'.$faker->randomDigit));
            $article->setSlug($slugGenerator->generateSlug($title, $dateCreatedAt));
            $article->setCover($cover);
            $articles[] = $article;
        }

        foreach ($articles as $article) {
            $manager->persist($article);
        }

        for ($i = 0; $i < 27; ++$i) {
            $dateCreatedAt = $faker->dateTimeBetween('-10 years', 'now', 'Europe/Paris');
            $startDateEvent = $faker->dateTimeInInterval($dateCreatedAt, '+ 11 months', 'Europe/Paris');
            $title = substr($faker->sentence(6, true),0,-1);
            $content = '
                <blockquote>'.$faker->sentence(6, true).'</blockquote>
                <h3>'.substr($faker->sentence(3, true),0,-1).'</h3>
                <p>'.$faker->paragraph(3, true).' <strong>'.substr($faker->sentence(2, false),0,-1).'</strong> '.$faker->sentence(5, true).' <a href="">'.substr($faker->sentence(3, false),0,-1).'</a>, '.$faker->paragraph(3, true).'</p>
                <p>'.$faker->paragraph(5, true).' <a href="">'.substr($faker->sentence(3, false),0,-1).'</a> '.$faker->paragraph(3, true).'</p>
            ';

            $event = new Post();
            $event->setCategory('event');
            $event->setCreatedAt($dateCreatedAt);
            $event->setContent($content);
            $event->setTitle($title);
            $event->setAuthor($this->getReference('user-'.$faker->randomDigit));
            $event->setSlug($slugGenerator->slugify($title));
            $event->setLocation($faker->city.' ('.$faker->departmentNumber.')');
            $event->setStartDate($startDateEvent);
            // not too much events with no end dates
            if (2 < $faker->randomDigit) {
                $event->setEndDate($faker->dateTimeInInterval($startDateEvent, '+ 1 year', 'Europe/Paris'));
            }
            $events[] = $event;
        }

        foreach ($events as $event) {
            $manager->persist($event);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return [
            UserFixtures::class,
        ];
    }
}
