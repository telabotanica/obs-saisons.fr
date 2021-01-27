<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\Species;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class EditablePosts
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getFilteredPosts(User $user, bool $isAdminDashboard = false): array
    {
        $postRepository = $this->em->getRepository(Post::class);
        $categories = array_keys(Post::CATEGORY_PARENT_ROUTE);
        $categorizedPosts = [];

        foreach ($categories as $category) {
            $posts = [];

            switch ($category) {
                case Post::CATEGORY_EVENT:
                case Post::CATEGORY_NEWS:
                    $posts = $postRepository->setCategory($category, false)
                        ->findByAuthor($user);
                    break;
                case Post::CATEGORY_PAGE:
                case Post::CATEGORY_SPECIES:
                    if ($isAdminDashboard) {
                        $posts = $postRepository->setCategory($category, false)
                            ->findAll();
                    }
                    break;
                default:
                    break;
            }

            if (!empty($posts)) {
                if (Post::CATEGORY_SPECIES !== $category) {
                    $categorizedPosts[$category] = $posts;
                } else {
                    foreach ($posts as $post) {
                        $species = $this->em->getRepository(Species::class)
                            ->findOneBy(['post' => $post]);
                        if (!empty($species)) {
                            $categorizedPosts[$category][] = $species;
                        }
                    }
                }
            }
        }

        return $categorizedPosts;
    }
}
