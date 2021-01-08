<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Service\SlugGenerator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class PostFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager)
    {
        $newsPosts = [];
        $eventsPosts = [];

        $faker = Faker\Factory::create('fr_FR');

        $slugGenerator = new SlugGenerator();

        for ($i = 0; $i < 27; ++$i) {
            $title = substr($faker->sentence(6, true), 0, -1);
            $dateCreatedAt = $faker->dateTimeBetween('-10 years', 'now', 'Europe/Paris');
            $cover = '/media/layout/image_station.png';
            $content = '
                <blockquote>'.$faker->sentence(6, true).'</blockquote>
                <figure>
                    <img src="'.$cover.'" alt="">
                    <figcaption>'.$faker->sentence(4, true).'</figcaption>
                </figure>
                <h3>'.substr($faker->sentence(3, true), 0, -1).'</h3>
                <p>'.$faker->paragraph(3, true).' <strong>'.substr($faker->sentence(2, false), 0, -1).'</strong> '.$faker->sentence(5, true).' <a href="">'.substr($faker->sentence(3, false), 0, -1).'</a> '.$faker->paragraph(3, true).'</p>
                <p>'.$faker->paragraph(5, true).' <a href="">'.substr($faker->sentence(3, false), 0, -1).'</a>, '.$faker->paragraph(3, true).'</p>
            ';

            $newsPost = new Post();
            $newsPost->setCategory(Post::CATEGORY_NEWS);
            $newsPost->setCreatedAt($dateCreatedAt);
            $newsPost->setContent($content);
            $newsPost->setTitle($title);
            $newsPost->setAuthor($this->getReference('user-'.$faker->randomDigit));
            $newsPost->setSlug($slugGenerator->generateSlug($title, $dateCreatedAt));
            $newsPost->setCover($cover);
            $newsPost->setStatus(Post::STATUS_ACTIVE);

            $newsPosts[] = $newsPost;
        }

        foreach ($newsPosts as $newsPost) {
            $manager->persist($newsPost);
        }

        for ($i = 0; $i < 27; ++$i) {
            $dateCreatedAt = $faker->dateTimeBetween('-10 years', 'now', 'Europe/Paris');
            $startDateEvent = $faker->dateTimeInInterval($dateCreatedAt, '+ 11 months', 'Europe/Paris');
            $title = substr($faker->sentence(6, true), 0, -1);
            $content = '
                <blockquote>'.$faker->sentence(6, true).'</blockquote>
                <h3>'.substr($faker->sentence(3, true), 0, -1).'</h3>
                <p>'.$faker->paragraph(3, true).' <strong>'.substr($faker->sentence(2, false), 0, -1).'</strong> '.$faker->sentence(5, true).' <a href="">'.substr($faker->sentence(3, false), 0, -1).'</a>, '.$faker->paragraph(3, true).'</p>
                <p>'.$faker->paragraph(5, true).' <a href="">'.substr($faker->sentence(3, false), 0, -1).'</a> '.$faker->paragraph(3, true).'</p>
            ';

            $eventPost = new Post();
            $eventPost->setCategory(Post::CATEGORY_EVENT);
            $eventPost->setCreatedAt($dateCreatedAt);
            $eventPost->setContent($content);
            $eventPost->setTitle($title);
            $eventPost->setAuthor($this->getReference('user-'.$faker->randomDigit));
            $eventPost->setSlug($slugGenerator->slugify($title));
            $eventPost->setLocation($faker->city.' ('.$faker->departmentNumber.')');
            $eventPost->setStartDate($startDateEvent);
            // not too much events with no end dates
            if (2 < $faker->randomDigit) {
                $eventPost->setEndDate($faker->dateTimeInInterval($startDateEvent, '+ 1 year', 'Europe/Paris'));
            }
            $eventPost->setStatus(Post::STATUS_ACTIVE);
            $eventsPosts[] = $eventPost;
        }

        foreach ($eventsPosts as $eventPost) {
            $manager->persist($eventPost);
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
