<?php

namespace App\Command;

use App\Entity\User;
use App\Service\MailchimpSyncContact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SyncNewsletterSubscribersCommand extends Command
{
    protected static $defaultName = 'ods:newsletter:sync';

    const NOT_REGISTERED = 'pas inscrit';
    const STATUS_SUBSCRIBED = 'subscribed';
    const STATUS_UNSUBSCRIBED = 'unsubscribed';

    private $manager;
    /**
     * @var SymfonyStyle
     */
    private $io;
    private $mailchimpSyncService;
    private $params;
    private $usersUpdated = 0;
    private $nbUsers = 0;

    public function __construct(EntityManagerInterface $manager, MailchimpSyncContact $mailchimpSyncService, ParameterBagInterface $params)
    {
        $this->manager = $manager;
        $this->mailchimpSyncService = $mailchimpSyncService;
        $this->params = $params;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Sync user newsletter subscription with brevo')
            ->setHelp('Sync users subscription status');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $today = new \DateTime("now");
        $timeStarted = $today->format('d-m-Y H:i:s');
        $this->io->title(sprintf('script started at %s .', ($timeStarted)));
        $users = $this->manager->getRepository(User::class)->findBy(['status' => 1]);

        if ($users){
            foreach ($users as $user){
                $this->nbUsers++;
                $status = $this->mailchimpSyncService->checkBrevoMailingList($user);

                if ($user->getIsNewsletterSubscriber() == 0){
                    if (!$status || $status == self::NOT_REGISTERED || $status == self::STATUS_UNSUBSCRIBED){
                        continue;
                    } else if ($status == self::STATUS_SUBSCRIBED ) {
                        $user->setIsNewsletterSubscriber(1);
                        $this->manager->persist($user);
                        $this->usersUpdated++;
                        $this->io->comment(sprintf('User: %s subscribed', $user->getEmail()));
                    }
                } else {
                    if (!$status || $status == self::NOT_REGISTERED || $status == self::STATUS_UNSUBSCRIBED){
                        $user->setIsNewsletterSubscriber(0);
                        $this->manager->persist($user);
                        $this->usersUpdated++;
                        $this->io->comment(sprintf('Utilisateur: %s unsubscribed', $user->getEmail()));
                    }
                }
            }

            $this->manager->flush();
        }

        $end = new \DateTime("now");
        $timeFinished = $end->format('d-m-Y H:i:s');

        $this->io->success([
            sprintf('Success! %d users updates out of %d total users!',
                $this->usersUpdated, $this->nbUsers
            ),
            sprintf('Job started at %s, Job finished at %s',$timeStarted, $timeFinished)
        ]);

        return 0;
    }
}