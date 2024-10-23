<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Observation;
use App\Entity\Post;
use App\Entity\Species;
use App\Entity\Station;
use App\Entity\User;
use App\Form\ImageVerificationType;
use App\Form\NewsletterPostType;
use App\Form\NewsPostType;
use App\Form\PagePostType;
use App\Form\ProfileType;
use App\Form\SpeciesPostType;
use App\Form\StationType;
use App\Form\StatsType;
use App\Form\UserEmailEditAdminType;
use App\Form\UserPasswordEditAdminType;
use App\Helper\OriginPageTrait;
use App\Security\Voter\PostVoter;
use App\Service\BreadcrumbsGenerator;
use App\Service\EditablePosts;
use App\Service\EmailSender;
use App\Service\MailchimpSyncContact;
use App\Service\SlugGenerator;
use App\Service\Stats;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use function PHPUnit\Framework\isEmpty;


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

        $this->setOrigin($this->generateUrl('admin_species_list'));

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

        $this->setOrigin($this->generateUrl('admin_pages_list'));

        return $this->render('admin/pages.html.twig', [
            'pages' => $pages,
            'staticPagesList' => array_merge(BreadcrumbsGenerator::MENU, BreadcrumbsGenerator::OTHER_BREADCRUMBS,
			),
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

		$observations = $manager->getRepository(Observation::class)->findOrderedObsPerUser($user);

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

        $wasNewsletterSubscriber = $user->getIsNewsletterSubscriber();

        $form = $this->createForm(ProfileType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$wasNewsletterSubscriber && $user->getIsNewsletterSubscriber()) {
                $mailchimpSyncContact->subscribe($user);
            } elseif ($wasNewsletterSubscriber && !$user->getIsNewsletterSubscriber()) {
               $mailchimpSyncContact->unsubscribe($user);
            }

			// Add role admin if selected
			$role = $form->get('roles')->getData();
			$exist = false;
			foreach ($role as $roleElem){
				if($roleElem == 'ROLE_ADMIN'){
					$exist = true;
				}
			}
			$exist ? $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']) : $user->setRoles(['ROLE_USER']);

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
            $mailchimpSyncContact->unsubscribe($user);
        }

        $user->setStatus(User::STATUS_ACTIVE);

        $manager->flush();

        $this->addFlash('notice', 'La suppression de ce compte a bien été annulée');

        return $this->redirectToRoute('admin_user_dashboard', ['userId' => $userId]);
    }

	/**
	 * @Route("/admin/stations", name="admin_stations_list")
	 */
	public function deactivatedStationsList(EntityManagerInterface $manager)
	{
		$stations = $manager->getRepository(Station::class)->findAllDeactivatedStations();

		// find all users ordered by name
		$users = $manager->getRepository(User::class)
			->findBy([], ['email' => 'ASC']);

		$this->setOrigin($this->generateUrl('admin_stations_list'));

		return $this->render('admin/stations.html.twig', [
			'stations' => $stations,
		]);
	}

	/**
	 * @Route("/admin/user/{userId}/activate", name="admin_user_activate")
	 */
	public function adminUserActivate($userId,Request $request, EntityManagerInterface $manager)
	{
		$user = $manager->getRepository(User::class)
			->find($userId);

		if (!$user) {
			throw $this->createNotFoundException('L’utilisateur n’existe pas');
		}

		if (null === $user) {
			$this->addFlash('error', 'Ce token est inconnu.');

			return $this->redirectToRoute('admin_users_list');
		}

		if (User::STATUS_ACTIVE === $user->getStatus()) {
			$this->addFlash('notice', 'Cet utilisateur est déjà activé.');

			return $this->redirectToRoute('admin_user_dashboard',
					 ['userId' => $userId]
			);
		}

		if (User::STATUS_PENDING !== $user->getStatus()) {
			$this->addFlash('warning', 'Impossible d’activer cet utilisateur.');

			return $this->redirectToRoute('homepage');
		}

		$user->setResetToken(null);
		$user->setStatus(User::STATUS_ACTIVE);

		$manager->flush();

		$this->addFlash('notice', "Le compte avec l'email ".$user->getEmail()." a été activé");

		return $this->redirectToRoute('admin_users_list');
	}
    /**
     * @Route("/admin/stats", name="admin_stats")
     */
    public function getStats(EntityManagerInterface $manager, Request $request, Stats $statsService){
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        // Menu déroulant choix année
        $minYear = $manager->getRepository(Observation::class)->findMinYear();
        $years = $manager->getRepository(Observation::class)->findAllYears();

        $yearsIndexed = [];
        foreach ($years as $year){
            $yearsIndexed[$year] = $year;
        }
        $year = new \DateTime('now');
        $year = $year->format('Y');
        $form = $this->createForm(StatsType::class,$years, ['years'=>$yearsIndexed]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $year = $form->get('years')->getData();
        }

        // Indicateurs
        $stats = $statsService->getStats($year);

        return $this->render('admin/stats.html.twig', [
            'years' => $years,
            'min_year' => $minYear,
            'form' => $form->createView(),
            'stats' => $stats
        ]);
    }


    /**
     * @Route("/admin/image/{imageId}/dashboard", name="admin_verif_image", methods={"GET"})
     *
     */
    public function adminImageDashboard($imageId, Request $request, EntityManagerInterface $manager)
    {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        $observation = $manager->getRepository(Observation::class)->find($imageId);

        if (!$observation) {
            throw $this->createNotFoundException("L'image à vérifier n'existe pas");
        }
        $erreur = $this->get('session')->getFlashBag()->get('error');
        $er2 ='';
        foreach ($erreur as $er) {
            $er2 = $er;
        }

        $imageVerificationForm = $this->createForm(ImageVerificationType::class, $observation);

        $this->setOrigin($request->getPathInfo());

        return $this->render('admin/verif-image.html.twig', [
            'observation' => $observation,
            '$imageVerificationForm' => $imageVerificationForm->createView(),
            'erreur' =>$er2
        ]);

    }


    /**
     * @Route("/admin/image/{imageId}/handle-form-submission", name="handle_form_submission", methods={"POST"})
     */
    public function handleFormSubmission($imageId,
                                         Request $request,
                                         EntityManagerInterface $manager,
                                         EmailSender $mailer,
                                         RequestStack $requestStack)
    {
        $observation = $manager->getRepository(Observation::class)->find($imageId);
        $individual = $observation->getIndividual();
        $station = $individual->getStation();

        if (!$observation) {
            throw $this->createNotFoundException("L'image à vérifier n'existe pas");
        }

        $isPictureValid = $request->request->get('confirmRadio');
        $motifRefus = $request->request->get('motif');

        if ($isPictureValid == 0 OR empty($isPictureValid)) {
            $this->addFlash('error', "Vous devez choisir si l'image est acceptable ou non.");
            return $this->redirectToRoute('admin_verif_image', [
                'imageId' => $imageId
            ]);
        } elseif ($isPictureValid == 2) {
            if(empty($motifRefus) or $motifRefus == ""){
                $this->addFlash('error', "Vous devez saisir un motif.");
                return $this->redirectToRoute('admin_verif_image', [
                    'imageId' => $imageId
                ]);
            }

            $stationLink = $this->router->generate('stations_show', ['slug' => $individual->getStation()->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);

            // Get the site baseUrl to generate absolute URL
            $request = $requestStack->getCurrentRequest();
            $baseUrl = $request->getSchemeAndHttpHost();
            $pictureUrl = $baseUrl . $observation->getPicture();
            
            $template='emails/observation-image-rejected.html.twig';
            
            // Envoi du mail de refus
            $message = $this->renderView($template, [
                'user' => $observation->getUser()->getDisplayName(),
                'observation' => $observation,
                'individual' => $individual,
                'pictureUrl' => $pictureUrl,
                'stationLink' => $stationLink,
                'station' => $station,
                'motif' => $motifRefus
            ]);
            $mailer->send(
                $observation->getUser()->getEmail(),
                $mailer->getSubjectFromTitle($message, 'Image refusée'),
                $message
            );

            $this->changeObservation($manager,$observation,$isPictureValid,$motifRefus);
        }else{
            $this->changeObservation($manager,$observation,$isPictureValid);
        }
    }
    public function changeObservation($manager,$observation,$isPictureValid,$motifRefus=null){
        // Update the observation entity with the form data
        $observation->setIsPictureValid($isPictureValid);
        if($isPictureValid===2){
            $observation->setMotifRefus($motifRefus);
        }
        // Persist changes to the database
        $manager->flush();
        $this->addFlash('error', "Modification effectuée avec succes");
        return $this->redirectToRoute('admin_verif_image_list');
    }

//    Fonction qui donne toutes les images qui n'ont pas encore été vérifiées
    /**
     * @Route("/admin/images", name="admin_verif_image_list", methods={"GET"})
     *
     */
    public function getImageList(EntityManagerInterface $manager, Request $request){
        // Donne accès aux administrateurs uniquement
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        //code de gestion d'erreur afin de donner un message de réussite ou d'erreur
        $erreur = $this->get('session')->getFlashBag()->get('error');
        $er2 = '';
        foreach ($erreur as $er) {
            $er2 = $er;
        }

        //Prise en compte de l'id de l'espece sélectionner sur le dropdown
        $selectedSpeciesId = $request->query->get('species', '');

        //Prise en compte du statut demandé lors de la selection du dropdown
        $selectedStatus = $request->query->get('statut', '');

        //Prise en compte de l'id de l'utilisateur sélectionner sur le dropdown
        $selectedUserId = $request->query->get('user', '');

        //Prise en compte de l'id de l'event sélectionner sur le dropdown
        $selectedEventId = $request->query->get('stade', '');

        //Prise en compte de la valeur de tri pour la date de création
        $sort = $request->query->get('sort', '');
        //Initialisation de valeur par default afin d'eviter les erreurs
        $totalImages = 0;
        $users = [];
        $stades = [];
        $species = [];
        $images = [];

        // Ajout d'un système de pagination
        $page = $request->query->getInt('page', 1);
        $pageSize = 10; // Number of images per page
        $offset = ($page - 1) * $pageSize;



        //try-catch pour la gestion d'erreur qui peut arriver avec la bdd
        try{
            $totalImages = $manager->getRepository(Observation::class)
                ->countImages($selectedStatus, $selectedSpeciesId, $selectedUserId, $selectedEventId);

            $images = $manager->getRepository(Observation::class)
                ->findImages($selectedStatus, $selectedSpeciesId, $selectedUserId, $selectedEventId, $offset, $pageSize, $sort);

            $species = $manager->getRepository(Species::class)->findAllOrderedByTypeAndVernacularName();

            $users = $manager->getRepository(User::class)->findAllActiveMembers();

            $stades = $manager->getRepository(Event::class)->findAllObservable();


        }catch (\Exception $exception) {
            echo 'An error occurred: ' . $exception->getMessage();
        }
        $totalPages = ceil($totalImages / $pageSize);



        return $this->render('admin/verif-images-list.html.twig', [
            'users'=>$users,
            'stades'=>$stades,
            'images' => $images,
            'species' => $species,
            'page' => $page,
            'selectedSpeciesId' => $selectedSpeciesId,
            'selectedStatus' => $selectedStatus,
            'selectedUserId' => $selectedUserId,
            'selectedEventId' => $selectedEventId,
            'pageSize' => $pageSize,
            'totalPages' => $totalPages,
            'totalImages' => $totalImages,
            'erreur' => $er2
        ]);
    }



    /**
     * @Route("/admin/newsletters", name="admin_newsletters_list")
     *//*
    public function newsletterList(EntityManagerInterface $manager)
    {
        $newsletters= $manager->getRepository(Post::class)
            ->findBy(['category' => Post::CATEGORY_NEWSLETTER], ['createdAt' => 'DESC'])
        ;

        $this->setOrigin($this->generateUrl('admin_newsletters_list'));

        return $this->render('admin/newsletters.html.twig', [
            'newsletters' => $newsletters
        ]);
    }*/

    /**
     * @Route("/admin/newsletters/create/{mode}", defaults={"mode"="wysiwyg"}, name="admin_newsletters_create")
     *//*
    public function addNewsletter(
        $mode,
        Request $request,
        EntityManagerInterface $manager,
        SlugGenerator $slugGenerator,
        UrlGeneratorInterface $router
    ) {
        // TODO Voir affichage de l'image cover
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $date_created = new \DateTime();
        $newsletter = new Post();
        $newsletter->setContent('');
        $newsletter->setCategory(Post::CATEGORY_NEWSLETTER);
        $newsletter->setAuthor($this->getUser());
        $newsletter->setCreatedAt($date_created);
        $newsletter->setStatus(Post::STATUS_PENDING);

        $form = $this->createForm(NewsletterPostType::class, $newsletter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newsletter->setSlug($slugGenerator->generateSlug($newsletter->getTitle(), $date_created));

            $manager->persist($newsletter);
            $manager->flush();

            $this->addFlash('notice', 'La newsletter a été créé');

            $this->setOrigin($this->generateUrl('admin_newsletters_list'));

            return $this->redirectToRoute('admin_newsletters_list');
        }

        return $this->render('admin/newsletter-create.html.twig', [
            'post' => $newsletter,
            'editMode' => $mode,
            'form' => $form->createView(),
            'upload' => $router->generate('image_create'),
        ]);
    }*/

    /**
     * @Route("/admin/newsletters/{postId}/show", name="admin_newsletters_show")
     */
    /*
    public function showNewsletter(int $postId, EntityManagerInterface $manager){
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);
        $newsletter = $manager->getRepository(Post::class)->find($postId);
        if (!$newsletter) {
            throw $this->createNotFoundException('La newsletter n’existe pas');
        }

        return $this->render('emails/newsletter.html.twig', [
            'content' => $newsletter->getContent(),
            'cover' => $newsletter->getCover()
        ]);
    }
*/
    /**
     * @Route("/admin/newsletters/{postId}/edit/{mode}", defaults={"mode"="wysiwyg"}, name="admin_newsletters_edit")
     */
    /*
    public function editNewsletter(
        $mode,
        int $postId,
        Request $request,
        EntityManagerInterface $manager,
        SlugGenerator $slugGenerator,
        UrlGeneratorInterface $router
    ) {
        // TODO Voir affichage de l'image cover
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $newsletter = $manager->getRepository(Post::class)->find($postId);

        if (!$newsletter) {
            throw $this->createNotFoundException('La newsletter n’existe pas');
        }

        $form = $this->createForm(NewsletterPostType::class, $newsletter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($newsletter);
            $manager->flush();

            $this->addFlash('notice', 'La newsletter a été modifiée');

            return $this->redirectToRoute('admin_newsletters_list');
        }

        return $this->render('admin/newsletter-create.html.twig', [
            'post' => $newsletter,
            'editMode' => $mode,
            'form' => $form->createView(),
            'upload' => $router->generate('image_create'),
        ]);
    }
    */
}
