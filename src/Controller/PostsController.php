<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Form\CommentFormType;
use App\Form\EventPostType;
use App\Form\NewsPostType;
use App\Helper\OriginPageTrait;
use App\Security\Voter\PostVoter;
use App\Service\BreadcrumbsGenerator;
use App\Service\EmailSender;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class PostsController.
 */
class PostsController extends AbstractController
{
    use OriginPageTrait;
    /* ************************************************ *
     * news list
     * ************************************************ */

    /**
     * @Route("/actualites/{page<\d+>}", name="news_posts_list")
     */
    public function newsPostsList(
        Request $request,
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        int $page = 1
    ) {
        $limit = 10;
        $newsPostRepository = $manager->getRepository(Post::class)->setCategory(Post::CATEGORY_NEWS);
        $newsPosts = $newsPostRepository->findAllPaginatedPosts($page, $limit);
        $lastPage = ceil(count($newsPostRepository->findAll()) / $limit);

        $this->setOrigin($request->getPathInfo());

        return $this->render('pages/post/news-posts-list.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->setToRemoveFromPath('/'.$page)->getBreadcrumbs(),
            'newsPosts' => $newsPosts,
            'pagination' => [
                'currentPage' => $page,
                'lastPage' => $lastPage,
            ],
        ]);
    }

    /* ************************************************ *
     * news
     * ************************************************ */

    /**
     * @Route("/actualites/{slug<\d*\/\d*\/.+>}", name="news_post_single_show", methods={"GET", "POST"})
     */
    public function newsPostSingle(
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        string $slug,
        Request $request
    ) {
        $newsPostRepository = $manager->getRepository(Post::class)->setCategory(Post::CATEGORY_NEWS);
        $newsPost = $newsPostRepository->findBySlug($slug);

        if (null === $newsPost) {
            throw new NotFoundHttpException('La page demandée n’existe pas');
        }

        $comment = new Comment();
        $comment->setPost($newsPost);
        $comment->setUser($this->getUser());
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($comment);
            $manager->flush();
            $this->addFlash('notice', 'Votre commentaire a été ajouté');
            return $this->redirectToRoute('news_post_single_show', ['slug' => $slug]);
        }

        $commentsReposity = $manager->getRepository(Comment::class);
        $comments = $commentsReposity->findByPost($newsPost);

        $nextPreviousNewsPosts = $newsPostRepository->findNextPrevious($newsPost->getId());

        return $this->render('pages/post/news-post-single.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->setActiveTrail($slug, $newsPost->getTitle())
                ->getBreadcrumbs(Post::CATEGORY_PARENT_ROUTE[$newsPost->getCategory()]),
            'post' => $newsPost,
            'nextPreviousNewsPosts' => $nextPreviousNewsPosts,
            'comments' => $comments,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/actualites/new", name="news_post_create")
     *
     * @return RedirectResponse|Response
     */
    public function newsPostNew(
        Request $request,
        EntityManagerInterface $manager,
        SlugGenerator $slugGenerator,
        UrlGeneratorInterface $router
    ) {
        $this->denyAccessUnlessGranted(User::ROLE_USER);

        $newsPost = new Post();
        $newsPost->setCategory(Post::CATEGORY_NEWS);

        $form = $this->createForm(NewsPostType::class, $newsPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newsPost->setSlug($slugGenerator->generateSlug($newsPost->getTitle(), $newsPost->getCreatedAt()));

            $manager->persist($newsPost);
            $manager->flush();

            $this->addFlash('notice', 'L’article a été créé');

            $this->setOrigin($this->generateUrl('news_posts_list'));

            return $this->redirectToRoute('news_post_preview', [
                'postId' => $newsPost->getId(),
            ]);
        }

        return $this->render('pages/post/news-post-create.html.twig', [
            'post' => $newsPost,
            'form' => $form->createView(),
            'upload' => $router->generate('image_create'),
        ]);
    }

    /**
     * @Route("/actualites/{postId}/edit", name="news_post_edit")
     */
    public function newsPostEdit(
        Request $request,
        EntityManagerInterface $manager,
        UrlGeneratorInterface $router,
        int $postId
    ) {
        $newsPost = $manager->getRepository(Post::class)
            ->find($postId);

        if (!$newsPost) {
            throw $this->createNotFoundException('L’article n’existe pas');
        }

        $this->denyAccessUnlessGranted(
            PostVoter::EDIT,
            $newsPost,
            'Vous n’êtes pas autorisé à modifier cet article'
        );
	
		if ($newsPost->getStatus(Post::STATUS_ACTIVE)){
			$this->denyAccessUnlessGranted(
				User::ROLE_ADMIN,
				$newsPost,
				'L’actualité est déjà publié et ne peut pas être modifié'
			);
		}

        if (Post::CATEGORY_NEWS !== $newsPost->getCategory()) {
            throw $this->createAccessDeniedException('Votre demande d’édition ne correspond pas à un article.');
        }

        $form = $this->createForm(NewsPostType::class, $newsPost);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('notice', 'L’article a été modifié');

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'redirect' => $this->generateUrl('news_post_preview', [
                        'postId' => $newsPost->getId(),
                    ]),
                ]);
            }

            return $this->redirectToRoute('news_post_preview', [
                'postId' => $newsPost->getId(),
            ]);
        }

        return $this->render('pages/post/news-post-create.html.twig', [
            'post' => $newsPost,
            'form' => $form->createView(),
            'upload' => $router->generate('image_create'),
            'origin' => $this->getOrigin(),
        ]);
    }

    /**
     * @Route("/actualites/{postId}/preview", name="news_post_preview")
     */
    public function newsPostPreview(
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        int $postId
    ) {
        $newsPost = $manager->getRepository(Post::class)
            ->find($postId);

        if (null === $newsPost) {
            throw new NotFoundHttpException('L’article demandé n’existe pas');
        }

        $this->denyAccessUnlessGranted(
            PostVoter::EDIT,
            $newsPost,
            'Vous n’êtes pas autorisé à publier cet article'
        );
	
		if ($newsPost->getStatus(Post::STATUS_ACTIVE)){
			$this->denyAccessUnlessGranted(
				User::ROLE_ADMIN,
				$newsPost,
				'L’actualité est déjà publié et ne peut pas être modifié'
			);
		}

        return $this->render('pages/post/news-post-preview.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->setActiveTrail($newsPost->getSlug(), $newsPost->getTitle())
                ->getBreadcrumbs(Post::CATEGORY_PARENT_ROUTE[$newsPost->getCategory()]),
            'post' => $newsPost,
            'origin' => $this->getOrigin(),
        ]);
    }

    /* ************************************************ *
     * events
     * ************************************************ */

    /**
     * @Route("/evenements/{page<\d+>}", name="event_posts_list")
     */
    public function eventPostsList(
        Request $request,
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        int $page = 1
    ) {
        $limit = 10;
        $eventPostRepository = $manager->getRepository(Post::class)->setCategory(Post::CATEGORY_EVENT);
        $eventPosts = $eventPostRepository->findAllPaginatedPosts($page, $limit);
        $lastPage = ceil(count($eventPostRepository->findAll()) / $limit);
		if ($lastPage > 2){
			$lastPage = 2;
		}

        $this->setOrigin($request->getPathInfo());
        return $this->render('pages/post/event-posts-list.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->setToRemoveFromPath('/'.$page)->getBreadcrumbs(),
            'eventPosts' => $eventPosts,
            'pagination' => [
                'currentPage' => $page,
                'lastPage' => $lastPage,
            ],
        ]);
    }

    /* ************************************************ *
     * event
     * ************************************************ */

    /**
     * @Route("/evenements/new", name="event_post_create")
     */
    public function eventPostNew(
        Request $request,
        EntityManagerInterface $manager,
        SlugGenerator $slugGenerator,
        UrlGeneratorInterface $router
    ) {
        $this->denyAccessUnlessGranted(User::ROLE_USER);

        $eventPost = new Post();
        $eventPost->setCategory(Post::CATEGORY_EVENT);

        $form = $this->createForm(EventPostType::class, $eventPost);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $eventPost->setSlug($slugGenerator->generateSlug($eventPost->getTitle(), $eventPost->getCreatedAt()));

            $manager->persist($eventPost);
            $manager->flush();

            $this->addFlash('notice', 'L’évènement a été créé');

            $this->setOrigin($this->generateUrl('event_posts_list'));

            return $this->redirectToRoute('event_post_preview', [
                'postId' => $eventPost->getId(),
            ]);
        }

        return $this->render('pages/post/event-post-create.html.twig', [
            'post' => $eventPost,
            'form' => $form->createView(),
            'upload' => $router->generate('image_create'),
        ]);
    }

    /**
     * @Route("/evenements/{postId}/edit", name="event_post_edit")
     */
    public function eventPostEdit(
        Request $request,
        EntityManagerInterface $manager,
        UrlGeneratorInterface $router,
        int $postId
    ) {
        $eventPost = $manager->getRepository(Post::class)
            ->find($postId);

        if (!$eventPost) {
            throw $this->createNotFoundException('L’évènement n’existe pas');
        }
		
		$this->denyAccessUnlessGranted(
            PostVoter::EDIT,
            $eventPost,
            'Vous n’êtes pas autorisé à modifier cet évènement'
        );
	
		if ($eventPost->getStatus(Post::STATUS_ACTIVE)){
			$this->denyAccessUnlessGranted(
				User::ROLE_ADMIN,
				$eventPost,
				'L’évènement est déjà publié et ne peut pas être modifié'
			);
		}

        if (Post::CATEGORY_EVENT !== $eventPost->getCategory()) {
            throw $this->createAccessDeniedException('Votre demande d’édition ne correspond pas à un évènement.');
        }

        $form = $this->createForm(EventPostType::class, $eventPost);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('notice', 'L’évènement a été modifié');

            return $this->redirectToRoute('event_post_preview', [
                'postId' => $eventPost->getId(),
            ]);
        }

        return $this->render('pages/post/event-post-create.html.twig', [
            'post' => $eventPost,
            'form' => $form->createView(),
            'upload' => $router->generate('image_create'),
            'origin' => $this->getOrigin(),
        ]);
    }

    /**
     * @Route("/evenements/{postId}/preview", name="event_post_preview")
     */
    public function eventPostPreview(
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        int $postId
    ) {
        $eventPost = $manager->getRepository(Post::class)
            ->find($postId);

        if (null === $eventPost) {
            throw new NotFoundHttpException('L’évènement demandé n’existe pas');
        }
		
        $this->denyAccessUnlessGranted(
			PostVoter::EDIT,
            $eventPost,
            'Vous n’êtes pas autorisé à publier cet évènement'
        );
	
		if ($eventPost->getStatus(Post::STATUS_ACTIVE)){
			$this->denyAccessUnlessGranted(
				User::ROLE_ADMIN,
				$eventPost,
				'L’évènement est déjà publié et ne peut pas être modifié'
			);
		}

        return $this->render('pages/post/event-post-preview.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->setActiveTrail($eventPost->getSlug(), $eventPost->getTitle())
                ->getBreadcrumbs(Post::CATEGORY_PARENT_ROUTE[$eventPost->getCategory()]),
            'post' => $eventPost,
            'origin' => $this->getOrigin(),
        ]);
    }

    /**
     * @Route("/evenements/{slug<\d*\/?\d*\/?.+>}", name="event_post_single_show")
     */
    public function eventPostSingle(
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        string $slug
    ) {
        $eventPostRepository = $manager->getRepository(Post::class)->setCategory(Post::CATEGORY_EVENT);
        $eventPost = $eventPostRepository->findBySlug($slug);
        if (null === $eventPost) {
            throw new NotFoundHttpException('La page demandée n’existe pas');
        }

        $nextPreviousEventsPosts = $eventPostRepository->findNextPrevious($eventPost->getId());

        return $this->render('pages/post/event-post-single.html.twig', [
            'breadcrumbs' => $breadcrumbsGenerator->setActiveTrail($slug, $eventPost->getTitle())
                ->getBreadcrumbs(Post::CATEGORY_PARENT_ROUTE[$eventPost->getCategory()]),
            'post' => $eventPost,
            'nextPreviousEventsPosts' => $nextPreviousEventsPosts,
        ]);
    }

    /* ************************************************ *
     * delete post
     * ************************************************ */

    /**
     * @Route("/publications/{postId}/delete", name="post_delete")
     */
    public function postDelete(
        EntityManagerInterface $manager,
        int $postId
    ) {
        $post = $manager->getRepository(Post::class)
            ->find($postId);

        if (!$post) {
            throw $this->createNotFoundException('La publication n’existe pas');
        }

        $this->denyAccessUnlessGranted(
            PostVoter::EDIT,
            $post,
            'Vous n’êtes pas autorisé à supprimer cette publication'
        );
	
		if ($post->getStatus(Post::STATUS_ACTIVE)){
			$this->denyAccessUnlessGranted(
				User::ROLE_ADMIN,
				$post,
				'L’évènement est déjà publié et ne peut pas être modifié'
			);
		}
		
        $manager->remove($post);
        $manager->flush();

        $this->addFlash('notice', 'La publication a été supprimée');

        return $this->redirect(
            $this->generateOriginUrl(Post::CATEGORY_PARENT_ROUTE[$post->getCategory()])
        );
    }

    /* ************************************************ *
     * activate post
     * ************************************************ */

    /**
     * @Route("/publications/{postId}/publish", name="post_publish")
     */
    public function postPublish(
        EntityManagerInterface $manager,
        int $postId,
		EmailSender $mailer,
		UserInterface $user
    ) {
        $post = $manager->getRepository(Post::class)
            ->find($postId);

        if (!$post) {
            throw $this->createNotFoundException('La publication n’existe pas');
        }
	
		$url='https://www.obs-saisons.fr/';
		if ($post->getCategory() === 'event'){
			$cat = 'evenements/';
		} else {
			$cat = 'actualites/';
		}
		$postSlug =$url.$cat.$post->getSlug();
		
		// Si Admin on publie le post, sinon s'il n'est pas encore publié l'utilisateur le soumet à validation
		if ($this->isGranted(User::ROLE_ADMIN, $post)){
			$post->setStatus(Post::STATUS_ACTIVE);
			$manager->flush();
			
			//TODO envoyer un email de validation à l'auteur pour le tenir au courant
			
			$this->addFlash('notice', 'La publication a été activée');
		} else if ($this->isGranted(
			PostVoter::EDIT,
			$post
		)){
			if ($post->getStatus(Post::STATUS_ACTIVE)){
				$this->denyAccessUnlessGranted(
					User::ROLE_ADMIN,
					$post,
					'L’évènement est déjà publié et ne peut pas être modifié'
				);
			}
			
			$post->setStatus(Post::STATUS_PENDING);
			$manager->flush();
			
			//Envoie d'un mail aux admins pour validation
			$emailMessage = 'La publication suivante est en attente de validation :';
			$subject = 'Une publication ODS est en attente de validation';
			
			$message = $this->renderView('emails/post-validation.html.twig', [
				'userEmail' => $user->getEmail(),
				'subject' => $subject,
				'message' => $emailMessage,
				'link' => $postSlug,
				'postName' => $post->getTitle()
			]);
			
			$mailer->send(
				EmailSender::CONTACT_EMAIL,
				$mailer->getSubjectFromTitle($message),
				$message
			);
			
			$this->addFlash('notice', 'La publication a été envoyée aux administrateurs pour validation');
		} else {
			$this->denyAccessUnlessGranted(
				PostVoter::EDIT,
				$post,
				'Vous n’êtes pas autorisé à activer cette publication'
			);
		}

        return $this->redirect(
            $this->generateOriginUrl(Post::CATEGORY_PARENT_ROUTE[$post->getCategory()])
        );
    }
}
