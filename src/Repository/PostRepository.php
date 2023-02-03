<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method Post|null find($id, $lockMode = null, $lockVersion = null)
 * @method Post|null findOneBy(array $criteria, array $orderBy = null)
 * @method Post[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostRepository extends ServiceEntityRepository
{
    /**
     * @var Post[]|array
     */
    private $posts;

    /**
     * @var string
     */
    private $category;

    /**
     * PostRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return $this
     */
    public function setCategory(string $category, bool $filterActivePosts = true): self
    {
        if (!array_key_exists($category, Post::CATEGORY_PARENT_ROUTE)) {
            throw new InvalidArgumentException(sprintf('Category "%s" in not allowed', $category));
        }
        $this->category = $category;

        $queryParameterSet = $this->createQueryBuilder('p')
            ->andWhere('p.category = :val');
//        if ($filterActivePosts) {
//            $queryParameterSet->andWhere('p.status = :status');
//            $queryParameterSet->setParameter('status', Post::STATUS_ACTIVE);
//        }
        $queryParameterSet->setParameter('val', $category);
        if (Post::CATEGORY_EVENT === $category) {
            $queryOrdered = $queryParameterSet->addOrderBy('p.startDate', 'DESC')
                ->addOrderBy('p.endDate', 'DESC');
        } else {
            $queryOrdered = $queryParameterSet->orderBy('p.createdAt', 'DESC');
        }

        $this->posts = $queryOrdered->getQuery()->getResult();

        /*
        // uncomment to filter outdated events
        if (Post::CATEGORY_EVENT === $this->category) {
            $this->posts = array_filter($this->posts, 'self::filterOutdatedEventscallback');
        }
        */

        return $this;
    }

    /**
     * @return Post[]
     */
    public function findAll(): array
    {
        return $this->posts;
    }

    /**
     * @return Post[]
     */
    public function findAllPaginatedPosts(int $page = 1, int $limit = 10): array
    {
        if (1 > $page) {
            throw new NotFoundHttpException('La page demandée n’existe pas');
        }
        if (1 > $limit) {
            throw new InvalidArgumentException('La valeur de l’argument $limit est incorrecte (valeur : '.$limit.').');
        }
        $firstResult = ($page - 1) * $limit;

        if (!isset($this->posts[$firstResult]) && 1 != $page) {
            throw new NotFoundHttpException('La page demandée n’existe pas.'); // page 404, sauf pour la première page
        }

        return array_slice($this->posts, $firstResult, $limit);
    }

    public function findBySlug(string $slug): ?Post
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
     * @return Post[]
     */
    public function findByAuthor(User $user): array
    {
        $userPosts = [];
        foreach ($this->posts as $post) {
            if ($user === $post->getAuthor()) {
                $userPosts[] = $post;
            }
        }

        return $userPosts;
    }

    public function findLastFeaturedPosts(int $limit = 3): array
    {
        $filteredPosts = [];
		
		// Filter pending posts
		$this->posts = array_filter($this->posts, 'self::filterPendingPostscallback');
		
        //Filter outdated events
        if ($this->category === 'event'){
            $filteredPosts = array_filter($this->posts, 'self::filterOutdatedEventscallback');
        }

        // N'affiche sur la page d'accueil que les prochains événèments. S'il y en a pas, affiche les derniers
        if (count($filteredPosts) > 0){
            $this->posts = $filteredPosts;
        }
        $lastPublishedPost[0] = reset($this->posts);
        // validate limit
        if (1 >= $limit) {
            return $lastPublishedPost;
        }
        $postCount = count($this->posts);
        if ($postCount < $limit) {
            $limit = $postCount;
        }

        for ($i = 1; $i < $limit; ++$i) {
            $lastPublishedPost[$i] = next($this->posts);
        }
        reset($this->posts);

        return $lastPublishedPost;
    }

    public function findNextPrevious(int $id): array
    {
        $nextPrevious = [
            'previous' => $this->findNext($id),
            'next' => $this->findPrevious($id),
        ];

        return $nextPrevious;
    }

    public function findNext(int $id): ?Post
    {
        return $this->findNextOrPreviousPost($id);
    }

    public function findPrevious(int $id): ?Post
    {
        return $this->findNextOrPreviousPost($id, -1);
    }

    private function findNextOrPreviousPost(int $id, int $direction = 1): ?Post
    {
        $matchingPost = null;
        // validate $direction
        if (!1 === abs($direction)) {
            return $matchingPost;
        }

        $referencePost = $this->find($id);
        foreach ($this->posts as $key => $post) {
            if ($referencePost === $post) {
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
     * @throws \Exception
     */
    public function filterOutdatedEventscallback(Post $post): bool
    {
        if ($post->getEndDate()) {
            $isValidPost = ($post->getEndDate() >= (new \DateTime('now')));
        } else {
            $isValidPost = ($post->getstartDate() >= (new \DateTime('now')));
        }

        return $isValidPost;
    }
	
	public function filterPendingPostscallback(Post $post): bool
	{
		$isValidPost =($post->getStatus() == 1);
		
		return $isValidPost;
	}
}
