<?php

namespace App\Controller;

// use App\Entity\LogEvent;
use App\Entity\User;
use App\Security\Voter\UserVoter;
use App\Service\EmailSender;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
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

class UserController extends AbstractController
{
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
    public function loginPage()
    {
        if ($this->isGranted(UserVoter::LOGGED)) {
            $this->addFlash('notice', 'Vous êtes déjà connecté·e.');

            return $this->redirectToRoute('accueil');
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
     * @throws \Exception
     */
    public function register(
            Request $request,
            ObjectManager $manager,
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
            ObjectManager $manager,
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
            ObjectManager $manager,
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
                    $this->getParameter('plateform')['from'],
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
            ObjectManager $manager,
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
     * @Route("/user/dashboard", name="user_dashboard")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function dashboard(
            Request $request,
            ObjectManager $manager
    ) {
        $this->denyAccessUnlessGranted(UserVoter::LOGGED);

        /**
         * @var User
         */
        $user = $this->getUser();

        if (empty($user->getName())) {
            // return $this->redirectToRoute( 'user_profile_create' );
        }

        return $this->render('pages/user/dashboard.html.twig');
    }
}
