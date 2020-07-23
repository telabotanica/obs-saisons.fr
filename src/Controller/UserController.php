<?php

namespace App\Controller;

// use App\Entity\LogEvent;
use App\Entity\Observation;
use App\Entity\Station;
use App\Entity\User;
use App\Form\ProfileType;
use App\Form\UserEmailType;
use App\Form\UserPasswordType;
use App\Security\Voter\UserVoter;
use App\Service\BreadcrumbsGenerator;
use App\Service\EmailSender;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class UserController extends AbstractController
{
    use TargetPathTrait;

    /**
     * Login form can be embed in pages.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginForm(AuthenticationUtils $authenticationUtils)
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if (!empty($error)) {
            $key = $error->getMessageKey();
            if ('Invalid credentials.' === $key) {
                $key = 'Mot de passe incorrect';
            }

            $this->addFlash('error', $key);
        }

        return $this->render('forms/user/login.html.twig', [
                'last_username' => $lastUsername,
        ]);
    }

    /**
     * @Route("/user/login", name="user_login")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginPage(SessionInterface $session)
    {
        if ($this->isGranted(UserVoter::LOGGED)) {
            $this->addFlash('notice', 'Vous êtes déjà connecté·e.');
            $previousPageUrl = $this->getTargetPath($session, 'main');
            if (null === $previousPageUrl) {
                return $this->redirectToRoute('homepage');
            }

            return $this->redirect($previousPageUrl);
        }

        return $this->render('pages/user/login.html.twig');
    }

    /**
     * @Route("/user/logout", name="user_logout")
     */
    public function logout()
    {
        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/user/register", name="user_register")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws Exception
     */
    public function register(
            Request $request,
            EntityManagerInterface $manager,
            EmailSender $mailer,
            TokenGeneratorInterface $tokenGenerator,
            UserPasswordEncoderInterface $passwordEncoder
    ) {
        if ($request->isMethod('POST') && ('register' === $request->request->get('action'))) {
            $userRepository = $manager->getRepository(User::class);

            if ($userRepository->findOneBy(['email' => $request->request->get('email')])) {
                $this->addFlash('error', 'Cet utilisateur est déjà enregistré.');

                return $this->redirectToRoute('user_login');
            }

            $user = new User();
            $user->setCreatedAt(new DateTime());
            $user->setEmail($request->request->get('email'));
            $user->setPassword($passwordEncoder->encodePassword($user, $request->request->get('password')));
            $user->setRoles([User::ROLE_USER]);
            $user->setStatus(User::STATUS_PENDING);

            $token = $tokenGenerator->generateToken();
            $user->setResetToken($token);

            $manager->persist($user);

            // // Log Event

            // $log = new LogEvent();
            // $log->setType( LogEvent::USER_REGISTER );
            // $log->setUser( $user );
            // $log->setCreatedAt( new \DateTime() );
            // $manager->persist( $log );

            // --

            $manager->flush();

            $message = $this->renderView('emails/register-activation.html.twig', [
                    'user' => $user,
                    'url' => $this->generateUrl('user_activate', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            $mailer->send(
                    'contact@obs-saisons.fr',
                    $user->getEmail(),
                    $mailer->getSubjectFromTitle($message),
                    $message
            );

            $this->addFlash('notice', 'Un email d’activation vous a été envoyé. Regardez votre boite de reception.');

            return $this->redirectToRoute('homepage');
        }

        return $this->redirectToRoute('user_login');
    }

    /**
     * @Route("/user/activate/{token}", name="user_activate")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function activate(
            Request $request,
            string $token,
            EntityManagerInterface $manager,
            SessionInterface $session,
            TokenStorageInterface $tokenStorage,
            EventDispatcherInterface $eventDispatcher
    ) {
        /**
         * @var User
         */
        $user = $manager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

        if (null === $user) {
            $this->addFlash('error', 'Ce token est inconnu.');

            return $this->redirectToRoute('homepage');
        }

        if (User::STATUS_ACTIVE === $user->getStatus()) {
            $this->addFlash('notice', 'Cet utilisateur est déjà activé.');

            return $this->redirectToRoute('homepage');
        }

        if (User::STATUS_PENDING !== $user->getStatus()) {
            $this->addFlash('warning', 'Impossible d’activer cet utilisateur.');

            return $this->redirectToRoute('homepage');
        }

        $user->setResetToken(null);
        $user->setStatus(User::STATUS_ACTIVE);

        $manager->flush();

        // Manual login

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $tokenStorage->setToken($token);
        $session->set('_security_main', serialize($token));
        $event = new InteractiveLoginEvent($request, $token);
        $eventDispatcher->dispatch('security.interactive_login', $event);

        // Redirect to profile

        $this->addFlash('notice', '<strong>Merci d’avoir confirmé votre adresse e-mail</strong><br> Pour commencer, complétez le profil ci-dessous.');

        return $this->redirectToRoute('user_profile_create');
    }

    /**
     * @Route("/user/password", name="user_forgotten_password")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function forgottenPassword(
            Request $request,
            EntityManagerInterface $manager,
            EmailSender $mailer,
            TokenGeneratorInterface $tokenGenerator
    ) {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            /**
             * @var User
             */
            $user = $manager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (null === $user) {
                $this->addFlash('warning', 'Cet utilisateur est inconnu.');

                return $this->redirectToRoute('homepage');
            }

            if (User::STATUS_ACTIVE !== $user->getStatus()) {
                $this->addFlash('error', 'Cet utilisateur est désactivé.');

                return $this->redirectToRoute('homepage');
            }

            $token = $tokenGenerator->generateToken();

            try {
                $user->setResetToken($token);
                $manager->flush();
            } catch (Exception $e) {
                $this->addFlash('warning', $e->getMessage());

                return $this->redirectToRoute('homepage');
            }

            $message = $this->renderView('emails/forgotten-password.html.twig', [
                    'user' => $user,
                    'url' => $this->generateUrl('user_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            $mailer->send(
                'contact@obs-saisons.fr',
                $user->getEmail(),
                $mailer->getSubjectFromTitle($message),
                $message
            );

            $this->addFlash('notice', 'Un email vous a été envoyé. Regardez votre boite de reception.');

            return $this->redirectToRoute('homepage');
        }

        return $this->render('pages/user/password-forgotten.html.twig');
    }

    /**
     * @Route("/user/password/reset/{token}", name="user_reset_password")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function resetPassword(
            Request $request,
            string $token,
            EntityManagerInterface $manager,
            UserPasswordEncoderInterface $passwordEncoder
    ) {
        if ($request->isMethod('POST')) {
            /**
             * @var User
             */
            $user = $manager->getRepository(User::class)->findOneBy(['resetToken' => $token]);

            if (null === $user) {
                $this->addFlash('error', 'Ce token est inconnu.');

                return $this->redirectToRoute('homepage');
            }

            $user->setPassword($passwordEncoder->encodePassword($user, $request->request->get('password')));
            $user->setResetToken(null);
            $manager->flush();

            $this->addFlash('notice', 'Mot de passe mis à jour, vous pouvez désormais vous identifier.');

            return $this->redirectToRoute('user_login');
        } else {
            return $this->render('pages/user/password-reset.html.twig', ['token' => $token]);
        }
    }

    /**
     * @Route("/user/dashboard", name="user_dashboard", methods={"GET"})
     */
    public function userDashboard(
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator
    ) {
        $this->denyAccessUnlessGranted(UserVoter::LOGGED);

        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);

        $stations = $manager->getRepository(Station::class)
            ->findBy(['user' => $user]);
        $observations = $manager->getRepository(Observation::class)
            ->findBy(['user' => $user]);
        foreach ($observations as $observation) {
            $obsStation = $observation->getIndividual()->getStation();
            if (!in_array($obsStation, $stations)) {
                $stations[] = $obsStation;
            }
        }

        return $this->render('pages/user/dashboard.html.twig', [
            'user' => $user,
            'stations' => $stations,
            'observations' => $observations,
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs('/dashboard'),
            'profileForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/user/{userId}/profile", name="user_profile", methods={"GET"})
     */
    public function dashboard(
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        int $userId
    ) {
        $this->denyAccessUnlessGranted(UserVoter::LOGGED);

        $userForProfile = $manager->getRepository(User::class)->find($userId);
        if (!$userForProfile) {
            throw $this->createNotFoundException('L’utilisateur n’existe pas');
        }

        $stations = $manager->getRepository(Station::class)
            ->findBy(['user' => $userForProfile]);
        $observations = $manager->getRepository(Observation::class)
            ->findBy(['user' => $userForProfile]);
        foreach ($observations as $observation) {
            $obsStation = $observation->getIndividual()->getStation();
            if (!in_array($obsStation, $stations)) {
                $stations[] = $obsStation;
            }
        }

        return $this->render('pages/user/dashboard.html.twig', [
            'user' => $userForProfile,
            'stations' => $stations,
            'observations' => $observations,
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs('/dashboard'),
        ]);
    }

    /**
     * @Route("/user/{userId}/dashboard/admin", name="user_dashboard_admin")
     */
    public function userDashboardAdmin(
        EntityManagerInterface $manager,
        BreadcrumbsGenerator $breadcrumbsGenerator,
        int $userId
    ) {
        $this->denyAccessUnlessGranted(User::ROLE_ADMIN);

        $user = $manager->getRepository(User::class)->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('L’utilisateur n’existe pas');
        }

        $form = $this->createForm(ProfileType::class, $user);

        $stations = $manager->getRepository(Station::class)
            ->findBy(['user' => $user]);
        $observations = $manager->getRepository(Observation::class)
            ->findBy(['user' => $user]);
        foreach ($observations as $observation) {
            $obsStation = $observation->getIndividual()->getStation();
            if (!in_array($obsStation, $stations)) {
                $stations[] = $obsStation;
            }
        }

        return $this->render('pages/user/dashboard.html.twig', [
            'user' => $user,
            'stations' => $stations,
            'observations' => $observations,
            'breadcrumbs' => $breadcrumbsGenerator->getBreadcrumbs('/dashboard'),
            'profileForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/profile/create", name="user_profile_create")
     */
    public function profileCreate(
        Request $request,
        EntityManagerInterface $manager
    ) {
        if (!$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $manager->flush();

                $this->addFlash('success', 'Votre profil a été créé');
            } else {
                $this->addFlash('error', 'Le profil n’a pas pu être créé');
            }

            return $this->redirectToRoute('user_dashboard');
        }

        return $this->render('forms/user/profile-form-page.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/profile/edit", name="user_profile_edit", methods={"POST"})
     */
    public function profileEdit(
        Request $request,
        EntityManagerInterface $manager
    ) {
        if (!$this->isGranted(UserVoter::LOGGED)) {
            return $this->redirectToRoute('user_login');
        }

        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('success', 'Votre profil a été modifié');
        } else {
            $this->addFlash('error', 'Le profil n’a pas pu être modifié');
        }

        return $this->redirectToRoute('user_dashboard');
    }

    /**
     * @Route("/user/parameters/edit", name="user_parameters_edit")
     */
    public function parametersEdit(
        Request $request,
        EntityManagerInterface $manager,
        UserPasswordEncoderInterface $passwordEncoder,
        TokenGeneratorInterface $tokenGenerator,
        EmailSender $mailer
    ) {
        $this->denyAccessUnlessGranted(UserVoter::LOGGED);

        /**
         * @var User $user
         */
        $user = $this->getUser();

        /**
         * @var User $emailUserSubmitted
         */
        $emailUserSubmitted = new User();
        $emailForm = $this->createForm(UserEmailType::class, $emailUserSubmitted);

        $vars = $request->request->get('user_email');

        if (!empty($vars['email_new'])) {
            $emailForm->handleRequest($request);
        }

        if ($emailForm->isSubmitted() && $emailForm->isValid()) {
            $userRepository = $manager->getRepository(User::class);

            if (!$passwordEncoder->isPasswordValid($user, $emailUserSubmitted->getPassword())) {
                $this->addFlash('error', 'Mot de passe incorrect');
            } elseif ($userRepository->findOneBy(['email' => $vars['email_new']])) {
                $this->addFlash('error', 'Cet utilisateur est déjà enregistré.');
            } else {
                $token = $tokenGenerator->generateToken();

                try {
                    $user->setEmailNew($vars['email_new']);
                    $user->setEmailToken($token);
                    $manager->flush();
                } catch (Exception $e) {
                    $this->addFlash('warning', $e->getMessage());
                }

                // Send Warning

                $message = $this->renderView('emails/email-change-warning.html.twig', [
                    'user' => $user,
                ]);

                $mailer->send(
                    'contact@obs-saisons.fr',
                    $user->getEmail(),
                    $mailer->getSubjectFromTitle($message),
                    $message
                );

                // Send Confirmation

                $message = $this->renderView('emails/email-change-confirm.html.twig', [
                    'user' => $user,
                    'url' => $this->generateUrl('user_email_confirm', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                ]);

                $mailer->send(
                    'contact@obs-saisons.fr',
                    $vars['email_new'],
                    $mailer->getSubjectFromTitle($message),
                    $message
                );

                $this->addFlash('notice', 'Un email de confirmation du changement d’adresse vous a été envoyé. Regardez la boite de reception de votre nouvelle adresse.');
            }
        }

        /**
         * @var User $passwordUserSubmitted
         */
        $passwordUserSubmitted = new User();
        $passwordForm = $this->createForm(UserPasswordType::class, $passwordUserSubmitted);

        $vars = $request->request->get('user_password');

        if (!empty($vars['password_new'])) {
            $passwordForm->handleRequest($request);
        }

        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            if ($vars['password_new'] !== $vars['password_confirm']) {
                $this->addFlash('error', 'Votre mot de passe et sa confirmation doivent être identiques');
            } elseif (!$passwordEncoder->isPasswordValid($user, $passwordUserSubmitted->getPassword())) {
                $this->addFlash('error', 'Mot de passe incorrect');
            } else {
                $user->setPassword($passwordEncoder->encodePassword($user, $vars['password_new']));
                $user->setResetToken(null);
                $manager->flush();

                $this->addFlash('notice', 'Votre mot de passe a été mis à jour.');

                return $this->redirectToRoute('user_dashboard');
            }
        }

        return $this->render('pages/user/parameters-edit.html.twig', [
            'emailForm' => $emailForm->createView(),
            'passwordForm' => $passwordForm->createView(),
        ]);
    }

    /**
     * @Route("/user/email-change/{token}", name="user_email_confirm")
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function emailChangeConfirm(
        string $token,
        EntityManagerInterface $manager
    ) {
        $this->denyAccessUnlessGranted(UserVoter::LOGGED);

        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();

        $userRepository = $manager->getRepository(User::class);

        if ($userRepository->findOneBy(['email' => $user->getEmailNew()])) {
            $this->addFlash('error', 'Cet utilisateur est déjà enregistré.');
        } elseif (($token !== $user->getEmailToken()) || (empty($user->getEmailNew()))) {
            $this->addFlash('error', 'Ce token est inconnu.');
        } else {
            $user->setEmail($user->getEmailNew());
            $user->setEmailToken(null);
            $manager->flush();

            $this->addFlash('notice', 'Votre adresse e-mail a été modifiée.');
        }

        return $this->redirectToRoute('user_dashboard');
    }
}
