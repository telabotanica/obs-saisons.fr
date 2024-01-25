<?php

namespace App\Command;

use App\Entity\User;
use App\Service\MailchimpSyncContact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UserExportNewsletterSubscribers extends Command
{
    protected static $defaultName = 'ods:newsletter:exportUser';

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
    private $nbUsers = 0;
    private $nbUsersExported = 0;
    private $nbExistingUsers = 0;

    public function __construct(EntityManagerInterface $manager, ParameterBagInterface $params, MailchimpSyncContact $mailchimpSyncService)
    {
        $this->manager = $manager;
        $this->params = $params;
        $this->mailchimpSyncService = $mailchimpSyncService;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Export newsletter subscribers to brevo');
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

        $users = $this->manager->getRepository(User::class)->findBy(['status' => 1, 'isNewsletterSubscriber' => 1]);

        if ($users){
            $usersToExport = [];
            foreach ($users as $user){
                $this->nbUsers++;
                $status = $this->mailchimpSyncService->checkBrevoMailingList($user);

                if ($status == self::STATUS_SUBSCRIBED ) {
                    $this->nbExistingUsers++;
                } else {
                    $usersToExport[] = ["email"=> $user->getEmail(), 'attributes' => [
                        'PRENOM' => $user->getName()
                    ],];
                    $this->nbUsersExported++;
                }
            }

            // Envoi vers brevo
            try {
                $this->mailchimpSyncService->exportSubscribers($usersToExport);
            } catch (\Exception $e) {
                $this->io->error(sprintf('Error while exporting subscribers to Brevo: %s ', $e->getMessage())
                );
            }
        }

        $end = new \DateTime("now");
        $timeFinished = $end->format('d-m-Y H:i:s');

        $this->io->success([
            sprintf('Success! %d subscribers, %d exported to Brevo contact list, %d already existing',
                 $this->nbUsers, $this->nbUsersExported, $this->nbExistingUsers
            ),
            sprintf('Job started at %s, Job finished at %s',$timeStarted, $timeFinished)
        ]);

        return 0;
    }
}