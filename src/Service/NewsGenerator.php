<?php

namespace App\Service;

use App\Entity\News;

/**
 * Class NewsGenerator.
 */
class NewsGenerator
{
    /**
     * @var array
     */
    public $articles;

    /**
     * @var array
     */
    public $events;

    /**
     * NewsGenerator constructor.
     */
    public function __construct()
    {
        $articles = [
            [
                'id' => 4,
                'title' => 'Les premiers bourgeonnements 2019 : observez',
                'cover' => 'https://images.pexels.com/photos/86715/nandina-buds-pink-floral-86715.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940',
                'author' => 'Sylvain Bouleau',
                'createdAt' => new \DateTime('2019-01-08'),
                'content' => '
                    <blockquote>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros.</blockquote>
                    <figure>
                        <img src="https://images.pexels.com/photos/86715/nandina-buds-pink-floral-86715.jpeg?auto=compress&cs=tinysrgb&dpr=2&h=650&w=940" alt="">
                        <figcaption>Un chouette oiseau</figcaption>
                    </figure>
                    <h3>Un autre sous-titre ici</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros. <strong>Sed vulputate</strong> vestibulum orci at lacinia. Suspendisse nibh eros, <a href="#">cursus a ante finibus</a>, egestas varius arcu. Suspendisse quis luctus tortor, eu imperdiet nisl. Donec eu metus sagittis, placerat sem vel, tincidunt nisl. In ut arcu justo. Pellentesque hendrerit quam nisi, a pretium orci scelerisque in. Etiam eget nunc enim. Vivamus et nisl malesuada, efficitur magna a, pulvinar velit.</p>
                    <p>Integer non enim faucibus augue tristique sodales. In sodales arcu eu mollis facilisis. Donec imperdiet ornare risus. Praesent lacinia justo at eleifend consectetur. Curabitur scelerisque varius ante ut ullamcorper. Donec sodales viverra dapibus. Vestibulum tempus neque tellus, ac iaculis leo euismod ac. <a href="#">Nullam nec facilisis</a> eros. Vivamus est purus, efficitur a odio ultrices, condimentum sollicitudin risus. Sed mi mi, pretium facilisis magna sit amet, auctor luctus ligula.</p>
                ',
                'slug' => '2019/01/les-premiers-bourgeonnements-2019-observez',
            ],
            [
                'id' => 12,
                'title' => 'Vos plus belles photos du début d\'année',
                'cover' => 'https://assets.website-files.com/5ce249c60b5f0ba8c825fa9f/5ce25406d0cadbda9e302c8b_chouette.jpg',
                'author' => 'Tela Botanica',
                'createdAt' => new \DateTime('2019-01-12'),
                'content' => '
                    <blockquote>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros.</blockquote>
                    <figure>
                        <img src="https://assets.website-files.com/5ce249c60b5f0ba8c825fa9f/5ce25406d0cadbda9e302c8b_chouette.jpg" alt="">
                        <figcaption>Un chouette oiseau</figcaption>
                    </figure>
                    <h3>Un autre sous-titre ici</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros. <strong>Sed vulputate</strong> vestibulum orci at lacinia. Suspendisse nibh eros, <a href="#">cursus a ante finibus</a>, egestas varius arcu. Suspendisse quis luctus tortor, eu imperdiet nisl. Donec eu metus sagittis, placerat sem vel, tincidunt nisl. In ut arcu justo. Pellentesque hendrerit quam nisi, a pretium orci scelerisque in. Etiam eget nunc enim. Vivamus et nisl malesuada, efficitur magna a, pulvinar velit.</p>
                    <p>Integer non enim faucibus augue tristique sodales. In sodales arcu eu mollis facilisis. Donec imperdiet ornare risus. Praesent lacinia justo at eleifend consectetur. Curabitur scelerisque varius ante ut ullamcorper. Donec sodales viverra dapibus. Vestibulum tempus neque tellus, ac iaculis leo euismod ac. <a href="#">Nullam nec facilisis</a> eros. Vivamus est purus, efficitur a odio ultrices, condimentum sollicitudin risus. Sed mi mi, pretium facilisis magna sit amet, auctor luctus ligula.</p>
                ',
                'slug' => '2019/01/vos-plus-belles-photos-du-debut-dannee',
            ],
            [
                'id' => 427,
                'title' => 'Comment reconnaître le cri du pinson des arbres ?',
                'cover' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ab/Chaffinch_%28Fringilla_coelebs%29.jpg/800px-Chaffinch_%28Fringilla_coelebs%29.jpg',
                'author' => 'Boulébill',
                'createdAt' => new \DateTime('2019-01-08'),
                'content' => '
                    <blockquote>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros.</blockquote>
                    <figure>
                        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/a/ab/Chaffinch_%28Fringilla_coelebs%29.jpg/800px-Chaffinch_%28Fringilla_coelebs%29.jpg" alt="">
                        <figcaption>Un chouette oiseau</figcaption>
                    </figure>
                    <h3>Un autre sous-titre ici</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros. <strong>Sed vulputate</strong> vestibulum orci at lacinia. Suspendisse nibh eros, <a href="#">cursus a ante finibus</a>, egestas varius arcu. Suspendisse quis luctus tortor, eu imperdiet nisl. Donec eu metus sagittis, placerat sem vel, tincidunt nisl. In ut arcu justo. Pellentesque hendrerit quam nisi, a pretium orci scelerisque in. Etiam eget nunc enim. Vivamus et nisl malesuada, efficitur magna a, pulvinar velit.</p>
                    <p>Integer non enim faucibus augue tristique sodales. In sodales arcu eu mollis facilisis. Donec imperdiet ornare risus. Praesent lacinia justo at eleifend consectetur. Curabitur scelerisque varius ante ut ullamcorper. Donec sodales viverra dapibus. Vestibulum tempus neque tellus, ac iaculis leo euismod ac. <a href="#">Nullam nec facilisis</a> eros. Vivamus est purus, efficitur a odio ultrices, condimentum sollicitudin risus. Sed mi mi, pretium facilisis magna sit amet, auctor luctus ligula.</p>
                ',
                'slug' => '2019/01/comment-reconnaitre-le-cri-du-pinson-des-arbres',
            ],
        ];

        foreach ($articles as $articleData) {
            $article = new News();
            $article->setCategory('article');
            $article->setId($articleData['id']);
            $article->setCreatedAt($articleData['createdAt']);
            $article->setContent($articleData['content']);
            $article->setTitle($articleData['title']);
            $article->setAuthor($articleData['author']);
            $article->setSlug($articleData['slug']);
            $article->setCover($articleData['cover']);

            $this->articles[] = $article;
        }

        $events = [
            [
                'id' => 3,
                'title' => 'Stage de terrain autour de St-Hippolyte-du-Fort',
                'start_date' => new \DateTime('2019-01-22'),
                'end_date' => new \DateTime('2019-01-26'),
                'author' => 'Coco L\'Asticot',
                'createdAt' => new \DateTime('2018-12-28'),
                'content' => '
                    <blockquote>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros.</blockquote>
                    <h3>Un autre sous-titre ici</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros. <strong>Sed vulputate</strong> vestibulum orci at lacinia. Suspendisse nibh eros, <a href="#">cursus a ante finibus</a>, egestas varius arcu. Suspendisse quis luctus tortor, eu imperdiet nisl. Donec eu metus sagittis, placerat sem vel, tincidunt nisl. In ut arcu justo. Pellentesque hendrerit quam nisi, a pretium orci scelerisque in. Etiam eget nunc enim. Vivamus et nisl malesuada, efficitur magna a, pulvinar velit.</p>
                    <p>Integer non enim faucibus augue tristique sodales. In sodales arcu eu mollis facilisis. Donec imperdiet ornare risus. Praesent lacinia justo at eleifend consectetur. Curabitur scelerisque varius ante ut ullamcorper. Donec sodales viverra dapibus. Vestibulum tempus neque tellus, ac iaculis leo euismod ac. <a href="#">Nullam nec facilisis</a> eros. Vivamus est purus, efficitur a odio ultrices, condimentum sollicitudin risus. Sed mi mi, pretium facilisis magna sit amet, auctor luctus ligula.</p>
                ',
                'location' => 'St-Hippolyte-du-Fort (30)',
                'slug' => 'stage-de-terrain-autour-de-st-hippolyte-du-fort',
            ],
            [
                'id' => 17,
                'title' => 'Exposition les 4 saisons de Soisson',
                'start_date' => new \DateTime('2019-04-04'),
                'end_date' => new \DateTime('2019-06-16'),
                'author' => 'Grégoire de Tours',
                'createdAt' => new \DateTime('2019-02-08'),
                'content' => '
                    <blockquote>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros.</blockquote>
                    <h3>Un autre sous-titre ici</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros. <strong>Sed vulputate</strong> vestibulum orci at lacinia. Suspendisse nibh eros, <a href="#">cursus a ante finibus</a>, egestas varius arcu. Suspendisse quis luctus tortor, eu imperdiet nisl. Donec eu metus sagittis, placerat sem vel, tincidunt nisl. In ut arcu justo. Pellentesque hendrerit quam nisi, a pretium orci scelerisque in. Etiam eget nunc enim. Vivamus et nisl malesuada, efficitur magna a, pulvinar velit.</p>
                    <p>Integer non enim faucibus augue tristique sodales. In sodales arcu eu mollis facilisis. Donec imperdiet ornare risus. Praesent lacinia justo at eleifend consectetur. Curabitur scelerisque varius ante ut ullamcorper. Donec sodales viverra dapibus. Vestibulum tempus neque tellus, ac iaculis leo euismod ac. <a href="#">Nullam nec facilisis</a> eros. Vivamus est purus, efficitur a odio ultrices, condimentum sollicitudin risus. Sed mi mi, pretium facilisis magna sit amet, auctor luctus ligula.</p>
                ',
                'location' => 'Soisson (02)',
                'slug' => 'exposition-les-4-saisons-de-soisson',
            ],
            [
                'id' => 29,
                'title' => 'Colloque "Le muguet pète un cable"',
                'start_date' => new \DateTime('2019-05-12'),
                'author' => 'Coq Licot',
                'createdAt' => new \DateTime('2019-03-08'),
                'content' => '
                    <blockquote>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros.</blockquote>
                    <h3>Un autre sous-titre ici</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros. <strong>Sed vulputate</strong> vestibulum orci at lacinia. Suspendisse nibh eros, <a href="#">cursus a ante finibus</a>, egestas varius arcu. Suspendisse quis luctus tortor, eu imperdiet nisl. Donec eu metus sagittis, placerat sem vel, tincidunt nisl. In ut arcu justo. Pellentesque hendrerit quam nisi, a pretium orci scelerisque in. Etiam eget nunc enim. Vivamus et nisl malesuada, efficitur magna a, pulvinar velit.</p>
                    <p>Integer non enim faucibus augue tristique sodales. In sodales arcu eu mollis facilisis. Donec imperdiet ornare risus. Praesent lacinia justo at eleifend consectetur. Curabitur scelerisque varius ante ut ullamcorper. Donec sodales viverra dapibus. Vestibulum tempus neque tellus, ac iaculis leo euismod ac. <a href="#">Nullam nec facilisis</a> eros. Vivamus est purus, efficitur a odio ultrices, condimentum sollicitudin risus. Sed mi mi, pretium facilisis magna sit amet, auctor luctus ligula.</p>
                ',
                'location' => 'Vichy (03)',
                'slug' => 'colloque-le-muguet-pete-un-cable',
            ],
            [
                'id' => 37,
                'title' => 'Inventaire d\'automne des Couleuvres de Montpellier',
                'start_date' => new \DateTime('2019-10-05'),
                'end_date' => new \DateTime('2019-11-01'),
                'author' => 'Philippe Frêche',
                'createdAt' => new \DateTime('2019-01-08'),
                'content' => '
                    <blockquote>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros.</blockquote>
                    <h3>Un autre sous-titre ici</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam semper lorem sollicitudin metus faucibus bibendum. Cras porttitor consequat lectus, eu imperdiet mauris feugiat vel. In volutpat nunc et tortor euismod, sit amet commodo dui eleifend. Curabitur non feugiat eros. <strong>Sed vulputate</strong> vestibulum orci at lacinia. Suspendisse nibh eros, <a href="#">cursus a ante finibus</a>, egestas varius arcu. Suspendisse quis luctus tortor, eu imperdiet nisl. Donec eu metus sagittis, placerat sem vel, tincidunt nisl. In ut arcu justo. Pellentesque hendrerit quam nisi, a pretium orci scelerisque in. Etiam eget nunc enim. Vivamus et nisl malesuada, efficitur magna a, pulvinar velit.</p>
                    <p>Integer non enim faucibus augue tristique sodales. In sodales arcu eu mollis facilisis. Donec imperdiet ornare risus. Praesent lacinia justo at eleifend consectetur. Curabitur scelerisque varius ante ut ullamcorper. Donec sodales viverra dapibus. Vestibulum tempus neque tellus, ac iaculis leo euismod ac. <a href="#">Nullam nec facilisis</a> eros. Vivamus est purus, efficitur a odio ultrices, condimentum sollicitudin risus. Sed mi mi, pretium facilisis magna sit amet, auctor luctus ligula.</p>
                ',
                'location' => 'Aimargues (30)',
                'slug' => 'inventaire-dautomne-des-couleuvres-de-montpellier',
            ],
        ];

        foreach ($events as $eventData) {
            $event = new News();
            $event->setCategory('event');
            $event->setId($eventData['id']);
            $event->setCreatedAt($eventData['createdAt']);
            $event->setContent($eventData['content']);
            $event->setTitle($eventData['title']);
            $event->setAuthor($eventData['author']);
            $event->setSlug($eventData['slug']);
            $event->setLocation($eventData['location']);
            $event->setStartDate($eventData['start_date']);
            if (isset($eventData['end_date'])) {
                $event->setEndDate($eventData['end_date']);
            }

            $this->events[] = $event;
        }
    }

    /**
     * @return News
     */
    public function generateNews(string $category): array
    {
        $news = ('article' === $category) ? $this->articles : $this->events;

        return $news;
    }
}
