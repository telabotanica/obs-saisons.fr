<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Species;
use App\Entity\User;
use App\Form\SpeciesPostType;
use App\Form\UserAdminType;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="home_admin")
     */
    public function index()
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    /**
     * @Route("/admin/especes", name="admin_species_list")
     */
    public function speciesList(EntityManagerInterface $manager)
    {
        $species = $manager->getRepository(Species::class)
            ->findAllOrderedByScientificName();

        return $this->render('admin/species.html.twig', [
            'species' => $species,
        ]);
    }

    /**
     * @Route("/admin/espece/{specyId}/edit", name="admin_edit_specy_page")
     */
    public function editSpeciesPage(
        $specyId,
        Request $request,
        EntityManagerInterface $manager,
        SlugGenerator $slugGenerator
    ) {
        $specy = $manager->getRepository(Species::class)->find($specyId);

        $specyPost = $specy->getPost();
        if (!$specyPost) {
            $specyPost = new Post();
            $specyPost->setContent('');
            $specyPost->setAuthor($this->getUser());
            $specyPost->setCategory(Post::CATEGORY_SPECY);
            $specyPost->setTitle('Fiche espèce '.$specy->getScientificName());
            $specyPost->setCreatedAt(new \DateTime());
            $specyPost->setSlug($slugGenerator->generateSlug($specyPost->getTitle(), $specyPost->getCreatedAt()));

            $manager->persist($specyPost);
        }

        $form = $this->createForm(SpeciesPostType::class, $specyPost);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('notice', 'Fiche modifiée');
        }

        if (!$specy->getPost()) {
            $specy->setPost($specyPost);

            $manager->flush();
        }

        return $this->render('admin/edit-species.html.twig', [
            'specy' => $specy,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/user/{userId}/edit", name="admin_edit_user")
     */
    public function editUser(
        $userId,
        Request $request,
        EntityManagerInterface $manager
    ) {
        $user = $manager->getRepository(User::class)
            ->findById($userId);

        if (!$user) {
            throw $this->createNotFoundException('L’utilisateur n’existe pas');
        }

        $form = $this->createForm(UserAdminType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('notice', 'Profil modifié');
        }
    }
}
