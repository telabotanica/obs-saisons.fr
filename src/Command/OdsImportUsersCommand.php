<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OdsImportUsersCommand extends Command
{
    protected static $defaultName = 'ods:import:users';

    private $em;
    private $container;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em)
    {
        $this->em = $em;
        $this->container = $container;

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

        $conn = $this->container->get('doctrine')->getConnection('ods_legacy');
        $importedUsers = $conn->fetchAll('
            SELECT DISTINCT du.name AS display_name,
                du.mail AS email,
                du.pass as password,
                du.status,
                CONCAT(IFNULL(p.first_name,\'\'),\' \',IFNULL(p.last_name,\'\')) AS name,
                p.post_code,
                p.locality,
                p.profile_type,
                p.is_newsletter_subscriber
            FROM `drupal_users` du JOIN (
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

        foreach ($importedUsers as $importedUser) {
            $io->text('creating user : '.$importedUser['display_name']);

            $user = new User();
            $user->setName($importedUser['name']);
            $user->setDisplayName($importedUser['display_name']);
            $user->setEmail($importedUser['email']);
            $user->setPassword($importedUser['password']);
            $user->setRoles(
                $this->getUserRoles($importedUser['email'])
            );
            $user->setStatus($importedUser['status']);
            if (preg_match('/^[\d]{5}$/', $importedUser['post_code'])) {
                $user->setPostCode($importedUser['post_code']);
            }
            $user->setLocality($importedUser['locality']);
            $user->setProfileType($importedUser['profile_type']);
            $user->setIsNewsletterSubscriber($importedUser['is_newsletter_subscriber']);
            $user->setIsMailsSubscriber(false);
            $user->setCreatedAt(new \DateTime());

            $this->em->persist($user);

            $io->text('...Ok.');
        }

        $this->em->flush();

        $io->success('Count users: '.count($importedUsers));

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
