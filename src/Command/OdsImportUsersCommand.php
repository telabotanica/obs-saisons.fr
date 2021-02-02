<?php

namespace App\Command;

use App\Entity\User;
use App\Service\EmailSender;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Twig\Environment;

class OdsImportUsersCommand extends Command
{
    protected static $defaultName = 'ods:import:users';

    private $em;
    private $managerRegistry;
    private $twig;
    private $router;
    private $tokenGenerator;
    private $mailer;

    public function __construct(
        EntityManagerInterface $em,
        ManagerRegistry $managerRegistry,
        Environment $twig,
        RouterInterface $router,
        TokenGeneratorInterface $tokenGenerator,
        EmailSender $mailer
    ) {
        $this->em = $em;
        $this->managerRegistry = $managerRegistry;
        $this->twig = $twig;
        $this->router = $router;
        $this->tokenGenerator = $tokenGenerator;
        $this->mailer = $mailer;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import users from legacy ODS database')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $conn = $this->managerRegistry->getConnection('ods_legacy');
        $legacyUsers = $conn->fetchAll('
            SELECT DISTINCT du.name AS display_name,
                du.uid,
                du.mail AS email,
                du.pass as password,
                du.created as created_at,
                du.status,
                CONCAT(IFNULL(p.first_name,\'\'),\' \',IFNULL(p.last_name,\'\')) AS name,
                p.post_code,
                p.locality,
                p.profile_type,
                p.is_newsletter_subscriber
            FROM `drupal_users` du 
            LEFT JOIN (
                SELECT pv.uid as uid,
                    MAX(CASE WHEN (pf.title = \'Nom\') THEN pv.value ELSE NULL END) as last_name,
                    MAX(CASE WHEN (pf.title = \'Prénom\') THEN pv.value ELSE NULL END) as first_name,
                    MAX(CASE WHEN (pf.title = \'Code postal\') THEN pv.value ELSE NULL END) as post_code,
                    MAX(CASE WHEN (pf.title = \'Ville\') THEN pv.value ELSE NULL END) as locality,
                    MAX(CASE WHEN (pf.title = \'Vous êtes un\') THEN pv.value ELSE NULL END) as profile_type,
                    MAX(CASE WHEN (pf.title = \'Abonnement à la lettre d\'\'actualités\') THEN pv.value ELSE 0 END) as is_newsletter_subscriber
            
                FROM `drupal_profile_values` pv
                LEFT JOIN `drupal_profile_fields` pf on pf.fid = pv.fid
                GROUP BY uid
                ORDER BY uid
            ) p ON du.uid = p.uid
            WHERE du.uid != 4;
        ');

        foreach ($legacyUsers as $legacyUser) {
            $io->text('creating user : '.$legacyUser['display_name']);

            $user = new User();
            $user->setName($legacyUser['name']);
            $user->setDisplayName($legacyUser['display_name']);
            $user->setEmail($legacyUser['email']);
            $user->setPassword($legacyUser['password']);
            $user->setRoles(
                $this->getUserRoles($legacyUser['email'])
            );
            $user->setStatus($legacyUser['status']);
            if (preg_match('/^[\d]{5}$/', $legacyUser['post_code'])) {
                $user->setPostCode($legacyUser['post_code']);
            }
            $user->setLocality($legacyUser['locality']);
            $user->setProfileType($legacyUser['profile_type']);
            $user->setIsNewsletterSubscriber($legacyUser['is_newsletter_subscriber'] ?? '0');
            $user->setIsMailsSubscriber(false);
            $user->setCreatedAt((new \DateTime())->setTimestamp($legacyUser['created_at']));
            $user->setResetToken('tok3nha5toBR3s3tedElMe0w');
            $user->setLegacyId($legacyUser['uid']);

            $this->em->persist($user);

            $io->text('...Ok.');
        }

        $this->em->flush();

        $io->success('Imported users: '.count($legacyUsers));

        $io->text('Disabling accounts with duplicated email');

        // find duplicated emails
        $qb = $this->em->createQueryBuilder();
        $dups = $qb
            ->addSelect('u')
            ->from(User::class, 'u')
            ->addGroupBy('u.email')
            ->andHaving($qb->expr()->gt($qb->expr()->count('u.email'), 1))
            ->getQuery()
            ->getResult()
        ;

        $count = 0;
        foreach ($dups as $duplicate) {
            /**
             * @var $duplicate User
             */
            // find users with duplicated emails
            $io->text('Searching user with email: '.$duplicate->getEmail());

            $qb = $this->em->createQueryBuilder();
            $users = $qb
                ->addSelect('u')
                ->from(User::class, 'u')
                ->andWhere($qb->expr()->eq('u.email', ':email'))
                ->setParameter(':email', $duplicate->getEmail())
                ->getQuery()
                ->getResult()
            ;

            $io->text('...accounts found: '.count($users));

            // disable user and change its email
            foreach ($users as $i => $user) {
                ++$count;

                /**
                 * @var $user User
                 */
                $user->setStatus(User::STATUS_DISABLED);
                $user->setEmail($user->getEmail().'_'.$i);
            }

            $io->text('...Ok.'.count($users).' accounts disabled');
        }

        $this->em->flush();

        $io->success(sprintf('Disabled email: %d; Disabled users: %d', count($dups), $count));

        // Now send new password emails
        $question = new ConfirmationQuestion('Send imported users password reset emails? (BEWARE, REAL THOUSANDS EMAILS SHIT) [y/N]', false);
        $mustSendNewPasswordEmails = $this->getHelper('question')->ask($input, $output, $question);

        if ($mustSendNewPasswordEmails) {
            $qb = $this->em->createQueryBuilder();
            $importedUsers = $qb
                ->addSelect('u')
                ->from(User::class, 'u')
                ->andWhere($qb->expr()->eq('u.status', ':status'))
                ->setParameter(':status', User::STATUS_ACTIVE)
                ->andWhere($qb->expr()->notIn('u.roles', ':role'))
                ->setParameter(':role', User::ROLE_ADMIN)
                ->andWhere($qb->expr()->eq('u.resetToken', ':token'))
                ->setParameter(':token', 'tok3nha5toBR3s3tedElMe0w')
                ->getQuery()
                ->getResult()
            ;

            $io->text('Sending imported users password reset emails: '.count($importedUsers));

            foreach ($importedUsers as $importedUser) {
                $token = $this->tokenGenerator->generateToken();
                /**
                 * @var $importedUser User
                 */
                $importedUser->setResetToken($token);

                $message = $this->twig->render('emails/reset-password.html.twig', [
                    'user' => $importedUser,
                    'url' => $this->router->generate('user_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                ]);

                $io->text('send email to: '.$importedUser->getEmail());

                $this->mailer->send(
                    'contact@obs-saisons.fr',
                    'killian.stefanini.tb@gmail.com',
//                    $importedUser->getEmail(),
                    'Merci de réinitialiser votre mot de passe',
                    $message
                );

                $io->text('...Ok.');

                // flushing each time to avoid sending mail and lost tokens in case of crash
                $this->em->flush();

                return 0;
            }

            $io->success('Done sending emails!');
        }

        return 0;
    }

    private function getUserRoles(string $email)
    {
        $userRoles = [User::ROLE_USER];
        if ('contact@obs-saisons.fr' === $email) {
            $userRoles[] = User::ROLE_ADMIN;
        }

        return $userRoles;
    }
}
