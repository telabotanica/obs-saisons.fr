<?php

namespace App\Repository;

use App\Entity\News;
use App\Service\NewsGenerator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Class NewsRepository
 * @package App\Repository
 */
class NewsRepository extends ServiceEntityRepository
{
    /**
     * @var array
     */
    private $news;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, News::class);
    }

    /**
     * @param $category
     * @return $this
     */
    public function setCategory($category) {
        $this->news = (new NewsGenerator())->generateNews($category);
        return $this;
    }

    /**
     * @return News|News[]|array
     */
    public function findAll() {
        return $this->news;
    }

    /**
     * @param string $slug
     * @return News[]
     */
    public function findBySlug(string $slug) {
        $lastLoop = count($this->news) -1;
        foreach ($this->news as $key => $news) {
            if($slug === $news->getSlug()) {
                break;

            } elseif ($key === $lastLoop) {
                return null;
            }
        }
        return $news;
    }

    /**
     * @param int $id
     * @return News[]
     */
    public function findById(int $id) {
        $lastLoop = count($this->news) -1;
        foreach ($this->news as $key => $news) {
            if($id === $news->getId()) {
                break;

            } elseif ($key === $lastLoop) {
                return null;

            }
        }
        return $news;
    }

    /**
     * @return News[]
     */
    public function findLast() {
        $news = end($this->news);
        reset($this->news);
        return $news;
    }

    /**
     * @param int $id
     * @return News[]|null
     */
    public function findNextPrevious(int $id)
    {
        // valid id
        if (!is_int($id)) return null;
        $nextPrevious = [
            'previous' => $this->findNext($id),
            'next' => $this->findPrevious($id)
        ];
        return $nextPrevious;
    }

    /**
     * @param int $id
     * @return News[]|null
     */
    public function findNext(int $id)
    {
        // valid id and direction
        if (!is_int($id)) return null;
        return $this->findNextOrPrevious($id, 1);
    }

    /**
     * @param int $id
     * @return News[]|null
     */
    public function findPrevious(int $id)
    {
        // valid id and direction
        if (!is_int($id)) return null;
        return $this->findNextOrPrevious($id, -1);
    }

    /**
     * @param int $id
     * @param int $direction
     * @return News[]|null
     */
    public function findNextOrPrevious(int $id, int $direction)
    {
        // valid id and direction
        if (!is_int($direction) || abs($direction) !== 1 || !is_int($id)) return null;
        $searchedNews = null;
        // find the news with the id = $id
        $newsKey = array_search($this->findById($id), $this->news);

        // find requested (next or previous) available article
        $searchedKey = $newsKey + ($direction * 1);
        while(
            !isset($this->news[$searchedKey]) &&
            $searchedKey >= 0 &&
            $searchedKey < count($this->news)
        ) {
            $searchedKey += $direction;
        }

        if(isset($this->news[$searchedKey])) {
            $searchedNews = $this->news[$searchedKey];

        }
        return $searchedNews;
    }
}