<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use App\Helper\ImportCommandTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Post;

class CreatePageCalendrierCommand extends Command
{
    use ImportCommandTrait;

    protected static $defaultName = 'ods:create:pageCalendrier';

    public $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Création page Calendrier des saisons')
            ->setHelp('Création page Calendrier des saisons')
        ;
        
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cmd = $this->em->getClassMetadata(Post::class);
        

        
            $pageExp = $this->em->getRepository(Post::class)->findOneBy(
                ['category' => Post::CATEGORY_PAGE, 'slug' => 'explorer-les-donnees']
            );
            $auteur = $pageExp->getAuthor();
            $category = $pageExp->getCategory();
            $page= new Post();
            $page->setAuthor($auteur);
            $page->setCategory($category);
            $page->setContent('');
            $page->setSlug('calendrier');
            $page->setTitle('Calendrier des saisons');
            $page->setStatus(1);
            
            $this->em->persist($page);
            $this->em->flush();

            return 0;
        
        
    }
}
