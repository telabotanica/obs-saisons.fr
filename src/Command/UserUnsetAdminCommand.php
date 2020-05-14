<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserUnsetAdminCommand extends Command
{
    protected static $defaultName = 'user:unset-admin';

    private $manager;

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Set User as ROLE_USER')
            ->setHelp('Set User as ROLE_USER');

        $this
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the user.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $this->manager->getRepository(User::class)
            ->findOneBy(['email' => $input->getArgument('email')]);

        if (empty($user)) {
            $output->writeln("<error>\n  User not found\n</error>");

            return 1;
        }

        $user->setRoles([User::ROLE_USER]);
        $this->manager->persist($user);
        $this->manager->flush();

        $output->writeln(sprintf('<info>User %s updated, has roles %s</info>', $user->getDisplayName(), implode(', ', $user->getRoles())));

        return 0;
    }
}
