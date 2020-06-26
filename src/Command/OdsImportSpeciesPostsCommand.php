<?php

namespace App\Command;

use App\Entity\Post;
use App\Entity\Species;
use App\Entity\TypeSpecies;
use App\Entity\User;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class OdsImportSpeciesPostsCommand extends Command
{
    protected static $defaultName = 'ods:import:species-posts';

    private $em;

    private $slugGenerator;

    public function __construct(EntityManagerInterface $em, SlugGenerator $slugGenerator)
    {
        $this->em = $em;

        $this->slugGenerator = $slugGenerator;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Import ODS species posts from legacy website')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $allSpecies = $this->em->getRepository(Species::class)->findAll();
        $client = HttpClient::create();

        $notFound = [];
        $admin = $this->em->getRepository(User::class)->findByRole(User::ROLE_ADMIN);

        foreach ($allSpecies as $species) {
            // trying to cover all naming inconsistency
            // sometime using vernacular or scientific name
            // sometime using hyphen or underscore
            $formattedNames = [];
            foreach (['-', '_'] as $separator) {
                $formattedNames[] = str_replace(' ', $separator, strtolower(trim($species->getVernacularName())));
                $formattedNames[] = str_replace(' ', $separator, strtolower(trim($species->getScientificName())));
            }

            // Iterating over each formatted names, should not return more than One element
            $response = array_reduce($formattedNames, function ($carry, $formattedName) use ($client, $io) {
                $url = "http://obs-saisons.fr/$formattedName";
                $response = $client->request('GET', $url);

                $code = $response->getStatusCode();
                if (200 !== $code) {
                    $io->writeln(sprintf('Got an error %d code on page %s', $code, $url));
                } else {
                    $carry = $response;
                }

                return $carry;
            });

            if (!$response) {
                $io->writeln(sprintf('<error>Oops, no content found for %s</error>', $species->getScientificName()));
                $notFound[] = $species->getScientificName();
                continue;
            }

            $content = $response->getContent(false);
            if (!$content) {
                $io->writeln(sprintf('<error>Oops, no content on page %s</error>', $response->getInfo('url')));
                continue;
            }

            // Yas! We got data! Let's crawl it
            $crawler = new Crawler($content);
            $html = $crawler->filter('#center  .content')->html();

            // Need to fix relatives uri for images links and href
            $html = preg_replace('@="/@', '="http://www.obs-saisons.fr/', $html);

            // Time to inject data to our db
            $speciesPost = $species->getPost();
            if (!$speciesPost) {
                $speciesPost = new Post();
                $speciesPost->setCategory(Post::CATEGORY_SPECIES);
                $speciesPost->setAuthor($admin);
                $speciesPost->setTitle('Fiche espèce '.$species->getScientificName());
                $speciesPost->setCreatedAt(new \DateTime());
                $speciesPost->setSlug($this->slugGenerator->generateSlug($speciesPost->getTitle(), $speciesPost->getCreatedAt()));
                $this->em->persist($speciesPost);
                $species->setPost($speciesPost);
            }
            $speciesPost->setContent($html);

            $io->writeln(sprintf('<info>Success for %s</info>', $species->getScientificName()));
        }

        $this->em->flush();

        $notFound = array_unique($notFound);
        foreach ($notFound as $i => $name) {
            if (!$i) {
                $io->writeln(sprintf('<info>Check as these %d missing species data:</info>', count($notFound)));
            }
            $io->writeln($name);
        }
        $io->success('Great, it’s done. Please check for missing species data listed above.');

        return 0;
    }
}
