<?php

namespace App\Controller;

use App\Entity\Observation;
use App\Entity\Post;
use App\Entity\Species;
use App\Entity\Station;
use App\Entity\User;
use App\Form\PagePostType;
use App\Form\ProfileType;
use App\Form\SpeciesPostType;
use App\Form\StationType;
use App\Form\UserEmailEditAdminType;
use App\Form\UserPasswordEditAdminType;
use App\Helper\OriginPageTrait;
use App\Service\BreadcrumbsGenerator;
use App\Service\EditablePosts;
use App\Service\MailchimpSyncContact;
use App\Service\SlugGenerator;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AdminController extends AbstractController
{
    use OriginPageTrait;
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

        $this->setOrigin($this->generateUrl('news_posts_list'));

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
        SlugGenerator $slugGenerator,
        UrlGeneratorInterface $router
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
            $speciesPost->setStatus(Post::STATUS_ACTIVE);

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
            'upload' => $router->generate('image_create'),
            'origin' => $this->getOrigin(),
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

        $this->setOrigin($this->generateUrl('news_posts_list'));

        return $this->render('admin/pages.html.twig', [
            'pages' => $pages,
            'staticPagesList' => array_merge(BreadcrumbsGenerator::MENU, BreadcrumbsGenerator::OTHER_BREADCRUMBS),
        ]);
    }

    /**
     * @throws \Exception
     * @Route("/admin/page/{slug}/edit/{mode}", defaults={"mode"="wysiwyg"}, name="admin_static_page_edit")
     */
    public function editStaticPage(
        $slug,
        $mode,
        Request $request,
        EntityManagerInterface $manager,
        UrlGeneratorInterface $router
    ) {
        if (!in_array($slug, BreadcrumbsGenerator::EDITABLE_PAGES)) {
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
            $page->setTitle(array_merge(BreadcrumbsGenerator::MENU, BreadcrumbsGenerator::OTHER_BREADCRUMBS)[$slug]);
            $page->setCreatedAt(new \DateTime());
            $page->setSlug($slug);
            $page->setStatus(Post::STATUS_ACTIVE);

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
            'upload' => $router->generate('image_create'),
            'origin' => $this->getOrigin(),
        ]);
    }

    /**
     * @Route("/admin/users", name="admin_users_list")
     */
    public function adminUsersList(EntityManagerInterface $manager)
    {
        // find all users ordered by name
        $users = $manager->getRepository(User::class)
            ->findBy([], ['email' => 'ASC'])
        ;

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    /**
     * @Route("/admin/user/{userId}/dashboard", name="admin_user_dashboard", methods={"GET"})
     */
    public function adminUserDashboard(
        $userId,
        Request $request,
        EntityManagerInterface $manager,
        EditablePosts $editablePosts
    ) {
        $user = $manager->getRepository(User::class)
            ->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('L’utilisateur n’existe pas');
        }

        $profileForm = $this->createForm(ProfileType::class, $user);

        $stations = $manager->getRepository(Station::class)
            ->findAllOrderedByLastActive($user);

        $station = new Station();
        $stationForm = $this->createForm(StationType::class, $station, [
            'action' => $this->generateUrl('stations_new'),
        ]);

        $observations = $manager->getRepository(Observation::class)
            ->findBy(['user' => $user]);

        // add stations user didn't create but contributed to
        foreach ($observations as $observation) {
            $obsStation = $observation->getIndividual()->getStation();
            if (!in_array($obsStation, $stations)) {
                $stations[] = $obsStation;
            }
        }

        $categorizedPosts = $editablePosts->getFilteredPosts($user);
        $this->setOrigin($request->getPathInfo());

        return $this->render('pages/user/dashboard.html.twig', [
            'isUserDashboardAdmin' => true,
            'user' => $user,
            'categorizedPosts' => $categorizedPosts,
            'stations' => $stations,
            'stationForm' => $stationForm->createView(),
            'observations' => $observations,
            'profileForm' => $profileForm->createView(),
        ]);
    }

    /**
     * @throws \Exception
     *
     * @Route("/admin/user/{userId}/profile/edit", name="admin_user_profile_edit", methods={"POST"})
     */
    public function adminProfileEdit(
        $userId,
        Request $request,
        EntityManagerInterface $manager,
        MailchimpSyncContact $mailchimpSyncContact
    ) {
        $user = $manager->getRepository(User::class)
            ->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('L’utilisateur n’existe pas');
        }

        $userWasNewsletterSubscriber = $user->getIsNewsletterSubscriber();

        $form = $this->createForm(ProfileType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$userWasNewsletterSubscriber && $user->getIsNewsletterSubscriber()) {
                $mailchimpSyncContact->subscribe($user);
            } elseif ($userWasNewsletterSubscriber && !$user->getIsNewsletterSubscriber()) {
                $mailchimpSyncContact->unsubscribe($user);
            }

            $manager->flush();

            $this->addFlash('success', 'Le profile de l’utilisateur a été modifié.');
        } else {
            $this->addFlash('error', 'Le profile de l’utilisateur n’a pas pu être modifié');
        }

        return $this->redirectToRoute('admin_user_dashboard', ['userId' => $userId]);
    }

    /**
     * @Route("/admin/user/{userId}/parameters/edit", name="admin_user_parameters_edit")
     */
    public function adminUserParametersEdit(
        $userId,
        Request $request,
        EntityManagerInterface $manager,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $user = $manager->getRepository(User::class)
            ->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('L’utilisateur n’existe pas');
        }

        //change email
        $emailForm = $this->createForm(UserEmailEditAdminType::class, $user);
        $userEmailFieldValue = $request->request->get('user_email_edit_admin');

        if (!empty($userEmailFieldValue['email_new'])) {
            $emailForm->handleRequest($request);
        }

        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            try {
                $user->setEmail($userEmailFieldValue['email_new']);
                $user->setEmailToken(null);
                $manager->flush();
                $this->addFlash('notice', 'L’email de l’utilisateur a été mis à jour.');
            } catch (\Exception $e) {
                $this->addFlash('warning', $e->getMessage());
            }

            return $this->redirectToRoute('admin_user_dashboard', ['userId' => $userId]);
        }

        //change password
        $passwordForm = $this->createForm(UserPasswordEditAdminType::class, $user);

        $userPasswordFieldValues = $request->request->get('user_password_edit_admin');

        if (!empty($userPasswordFieldValues['password']) && !empty($userPasswordFieldValues['password']['first'])) {
            $passwordForm->handleRequest($request);
        }

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $userPasswordFieldValue = $userPasswordFieldValues['password'];
            if ($userPasswordFieldValue['first'] !== $userPasswordFieldValue['second']) {
                $this->addFlash('error', 'Le mot de passe et sa confirmation doivent être identiques');
            } else {
                $user->setPassword($passwordEncoder->encodePassword($user, $userPasswordFieldValue['first']));
                $user->setResetToken(null);
                $manager->flush();

                $this->addFlash('notice', 'Le mot de passe de l’utilisateur a été mis à jour.');

                return $this->redirectToRoute('admin_user_dashboard', ['userId' => $userId]);
            }
        }

        return $this->render('admin/edit-user-parameters-page.html.twig', [
            'passwordForm' => $passwordForm->createView(),
            'emailForm' => $emailForm->createView(),
            'user' => $user,
        ]);
    }

    /**
     * @Route("/admin/user/{userId}/delete", name="admin_user_delete")
     */
    public function adminUserDelete(
        $userId,
        EntityManagerInterface $manager,
        MailchimpSyncContact $mailchimpSyncContact
    ) {
        $user = $manager->getRepository(User::class)
            ->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('L’utilisateur n’existe pas');
        }

        if ($user->getIsNewsletterSubscriber()) {
            $mailchimpSyncContact->unsubscribe($user);
        }

        $user->setDeletedAt(new DateTime());

        $manager->flush();

        $this->addFlash('notice', 'Le compte a bien été supprimé');

        return $this->redirectToRoute('admin_user_dashboard', ['userId' => $userId]);
    }

    /**
     * @Route("/admin/user/{userId}/delete/cancel", name="admin_user_cancel_delete")
     */
    public function adminUserCancelDelete(
        $userId,
        EntityManagerInterface $manager,
        MailchimpSyncContact $mailchimpSyncContact
    ) {
        $user = $manager->getRepository(User::class)
            ->find($userId);

        if (!$user) {
            throw $this->createNotFoundException('L’utilisateur n’existe pas');
        }

        $user->setDeletedAt(null);

        if ($user->getIsNewsletterSubscriber()) {
            $mailchimpSyncContact->subscribe($user);
        }

        $user->setStatus(User::STATUS_ACTIVE);

        $manager->flush();

        $this->addFlash('notice', 'La suppression de ce compte a bien été annulée');

        return $this->redirectToRoute('admin_user_dashboard', ['userId' => $userId]);
    }
}
