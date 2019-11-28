<?php

namespace App\Repository;

use App\Entity\Post;
use App\Service\PostsGenerator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class PostRepository.
 */
class PostRepository extends ServiceEntityRepository
{
    /**
     * @var array
     */
    private $posts;

    /**
     * @var null
     */
    private $category;

    /**
     * @var bool
     */
    private $isSorted;

    /**
     * PostRepository constructor.
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Post::class);
        $this->category = null;
        $this->isSorted = false;
    }

    /**
     * @return $this
     */
    public function setPosts(string $category)
    {
        $this->category = $category;
        $this->posts = (new PostsGenerator())->generatePosts($this->category);
        // uncomment to filter outdated events
        /*if ('event' === $this->category) {
            $this->posts = array_filter($this->posts, 'self::filterOutdatedEventscallback');
        }*/

        $this->isSorted = usort($this->posts, 'self::usortPostsCallback');

        return $this;
    }

    /**
     * @return Post|Post[]|array
     */
    public function findAll()
    {
        return $this->posts;
    }

    /**
     * @return Post
     */
    public function findBySlug(string $slug)
    {
        $ret = null;
        foreach ($this->posts as $post) {
            if ($slug === $post->getSlug()) {
                $ret = $post;
                break;
            }
        }

        return $ret;
    }

    /**
     * @return Post
     */
    public function findById(int $id)
    {
        $ret = null;
        foreach ($this->posts as $post) {
            if ($id === $post->getId()) {
                $ret = $post;
                break;
            }
        }

        return $ret;
    }

    /**
     * @return Post
     */
    public function findLastFeaturedPost()
    {
        return reset($this->posts);
    }

    /**
     * @return array
     */
    public function findLastFeaturedPosts(int $limit = 3)
    {
        $lastPublishedPost[0] = reset($this->posts);
        // validate limit
        if (0 >= $limit) {
            return $lastPublishedPost;
        }

        for ($i = 1; $i < $limit; ++$i) {
            $lastPublishedPost[$i] = next($this->posts);
        }
        reset($this->posts);

        return $lastPublishedPost;
    }

    /**
     * @return array
     */
    public function findNextPrevious(int $id)
    {
        $nextPrevious = [
            'previous' => $this->findNext($id),
            'next' => $this->findPrevious($id),
        ];

        return $nextPrevious;
    }

    /**
     * @return Post[]|null
     */
    public function findNext(int $id)
    {
        return $this->findNextOrPreviousPost($id);
    }

    /**
     * @return Post[]|null
     */
    public function findPrevious(int $id)
    {
        return $this->findNextOrPreviousPost($id, -1);
    }

    /**
     * @return Post[]|null
     */
    private function findNextOrPreviousPost(int $id, int $direction = 1)
    {
        $matchingPost = null;
        // validate $direction
        if (!1 === abs($direction)) {
            return $matchingPost;
        }

        $referencePost = $this->findById($id);
        foreach ($this->posts as $key => $post) {
            if ($referencePost == $post) {
                // posts are sorted from the most recent (or closest start date for events) to the oldest,
                // so next post is the one before in the $this->posts, previous post is the one after in $this->posts
                $matchingPostKey = $key - (1 * $direction);
                if (!empty($this->posts[$matchingPostKey])) {
                    $matchingPost = $this->posts[$matchingPostKey];
                    reset($this->posts);
                    break;
                }
            }
        }

        return $matchingPost;
    }

    /**
     * @return bool
     */
    public function filterOutdatedEventscallback(Post $post)
    {
        $validPost = ($post->getEndDate() >= (new \DateTime('now')));

        return $validPost;
    }

    /**
     * @return int
     */
    public function usortPostsCallback(Post $postA, Post $postB)
    {
        if ('event' === $this->category) {
            $compared = ($postA->getStartDate() <=> $postB->getStartDate());
            if (0 === $compared) {
                $compared = ($postA->getEndDate() <=> $postB->getEndDate());
            }
        }

        if (!isset($compared) || 0 === $compared) {
            $compared = -($postA->getCreatedAt() <=> $postB->getCreatedAt());
            if (0 === $compared) {
                $compared = strcmp($postA->getTitle(), $postB->getTitle());
            }
        }

        return $compared;
    }
}
