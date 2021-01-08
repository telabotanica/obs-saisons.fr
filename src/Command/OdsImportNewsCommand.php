<?php

namespace App\Command;

use App\Entity\Post;
use App\Entity\User;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class OdsImportNewsCommand extends Command
{
    protected static $defaultName = 'ods:import:news';

    private $managerRegistry;

    private $em;

    private $slugGenerator;

    private $admin;

    public function __construct(ManagerRegistry $managerRegistry, EntityManagerInterface $em, SlugGenerator $slugGenerator)
    {
        $this->managerRegistry = $managerRegistry;

        $this->em = $em;

        $this->slugGenerator = $slugGenerator;

        $this->admin = $this->em->getRepository(User::class)->findOneBy(['email' => 'contact@obs-saisons.fr']);

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import ODS news posts from legacy website')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $conn = $this->managerRegistry->getConnection('ods_legacy');

        $allImportingNews = $conn->fetchAll(
            'SELECT dn.nid as id,
                dn.title as title,
                dn.created as created_at,
                dnr.body as content,
                dn.uid as user_id,
                dn.status as status,
                df.filepath as file_path
            FROM `drupal_node` dn
            JOIN `drupal_node_revisions` dnr ON dn.`nid` = dnr.`nid`
            LEFT JOIN `drupal_content_field_image` dfi ON dfi.`nid` = dn.`nid`
            LEFT JOIN `drupal_files` df ON df.`fid` = dfi.`field_image_fid`
            WHERE dn.type = \'news\'
            OR (
                dn.type = \'articles_observateurs\'
                AND dn.`uid` = 1
            )'
        );

        $count = 0;
        foreach ($allImportingNews as $importingNews) {
            $user = $this->em->getRepository(User::class)->findOneBy(['legacyId' => $importingNews['user_id']]);

            if (!$user) {
                $io->text('Missing user: '.$importingNews['user_id']);
                $user = $this->admin;
            }

            $date = (new \DateTime())->setTimestamp($importingNews['created_at']);
            $cover = $importingNews['file_path'] ? 'https://www.obs-saisons.fr/'.$importingNews['file_path'] : '';

            $news = new Post();
            $news->setCategory(Post::CATEGORY_NEWS);
            $news->setSlug(substr($this->slugGenerator->generateSlug($importingNews['title'], $date), 0, 100));
            $news->setTitle(substr($importingNews['title'], 0, 100));
            $news->setContent($importingNews['content']);
            $news->setCover($cover);
            $news->setCreatedAt($date);
            $news->setAuthor($user);
            $news->setStatus(Post::STATUS_ACTIVE);

            $this->em->persist($news);
            ++$count;
        }

        $this->em->flush();

        $io->success('Imported news: '.$count.'/'.count($allImportingNews));

        return 0;
    }
}
