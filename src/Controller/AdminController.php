<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Species;
use App\Entity\User;
use App\Form\PagePostType;
use App\Form\SpeciesPostType;
use App\Form\UserAdminType;
use App\Service\BreadcrumbsGenerator;
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
            ->findAllOrderedByTypeAndVernacularName();

        return $this->render('admin/species.html.twig', [
            'speciesList' => $species,
        ]);
    }

    /**
     * @Route("/admin/espece/{speciesId}/edit/{mode}", defaults={"mode"="wysiwyg"}, name="admin_species_page_edit")
     */
    public function editSpeciesPage(
        $speciesId,
        $mode,
        Request $request,
        EntityManagerInterface $manager,
        SlugGenerator $slugGenerator
    ) {
        $species = $manager->getRepository(Species::class)->find($speciesId);

        $speciesPost = $species->getPost();
        if (!$speciesPost) {
            $speciesPost = new Post();
            $speciesPost->setContent('');
            $speciesPost->setAuthor($this->getUser());
            $speciesPost->setCategory(Post::CATEGORY_SPECIES);
            $speciesPost->setTitle('Fiche espèce '.$species->getScientificName());
            $speciesPost->setCreatedAt(new \DateTime());
            $speciesPost->setSlug($slugGenerator->generateSlug($speciesPost->getTitle(), $speciesPost->getCreatedAt()));

            $manager->persist($speciesPost);
        }

        $form = $this->createForm(SpeciesPostType::class, $speciesPost);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('notice', 'Fiche modifiée');
        }

        if (!$species->getPost()) {
            $species->setPost($speciesPost);

            $manager->flush();
        }

        return $this->render('admin/edit-species.html.twig', [
            'species' => $species,
            'editMode' => $mode,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/admin/pages", name="admin_pages_list")
     */
    public function pagesList(EntityManagerInterface $manager)
    {
        $pages = $manager->getRepository(Post::class)
            ->findBy(['category' => Post::CATEGORY_PAGE])
        ;

        return $this->render('admin/pages.html.twig', [
            'pages' => $pages,
            'staticPagesList' => BreadcrumbsGenerator::MENU,
        ]);
    }

    /**
     * @Route("/admin/page/{slug}/edit/{mode}", defaults={"mode"="wysiwyg"}, name="admin_static_page_edit")
     */
    public function editStaticPage(
        $slug,
        $mode,
        Request $request,
        EntityManagerInterface $manager
    ) {
        if (!array_key_exists($slug, BreadcrumbsGenerator::MENU)) {
            throw new \Exception('Slug is not part of the menu');
        }

        $page = $manager->getRepository(Post::class)->findOneBy([
            'category' => Post::CATEGORY_PAGE,
            'slug' => $slug,
        ]);
        if (!$page) {
            $page = new Post();
            $page->setContent('');
            $page->setAuthor($this->getUser());
            $page->setCategory(Post::CATEGORY_PAGE);
            $page->setTitle(BreadcrumbsGenerator::MENU[$slug]);
            $page->setCreatedAt(new \DateTime());
            $page->setSlug($slug);

            $manager->persist($page);
        }

        $form = $this->createForm(PagePostType::class, $page);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('notice', 'Page modifiée');
        }

        return $this->render('admin/edit-page.html.twig', [
            'page' => $page,
            'editMode' => $mode,
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
