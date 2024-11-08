<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Stats;
use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\BreadcrumbsGenerator;
use App\Form\PagePostType;
use App\Helper\OriginPageTrait;
use Symfony\Component\Security\Core\Security;

class RelayController extends AbstractController
{
    use OriginPageTrait;

    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security)
    {
       $this->security = $security;
    }
    /**
     * @Route("/relay", name="relay_home_page")
     */
    public function index(){
        $this->denyAccessUnlessGranted('ROLE_RELAY');
        
        return $this->render('relay/index.html.twig', [
           
        ]);
    }

    /**
     * @Route("/relay/global-stats", name="relay_global_stats")
     */
    public function getGlobalStats(Stats $statsService){
        $this->denyAccessUnlessGranted('ROLE_RELAY');

        // Indicateurs
        $stats = $statsService->getGlobalStats();
        
        return $this->render('relay/global-stats.html.twig', [
            'stats' => $stats
        ]);
    }

    /**
     * @throws \Exception
     * @Route("/relay/page/{slug}/edit/{mode}", defaults={"mode"="wysiwyg"}, name="relay_static_page_edit")
     */
    public function editStaticPage(
        $slug,
        $mode,
        Request $request,
        EntityManagerInterface $manager,
        UrlGeneratorInterface $router
    ) {
        
        $user = $this->security->getUser();
        if ($user->getTypeRelays()->getId()===1){
            $slug='ods-occitanie';
        }else if($user->getTypeRelays()->getId()===2){
            $slug='ods-provence';
        }
        $page = $manager->getRepository(Post::class)->findOneBy([
            'category' => Post::CATEGORY_PAGE,
            'slug' => $slug,
        ]);
        if (!$page) {
            $page = new Post();
            $page->setContent('');
            $page->setAuthor($this->getUser());
            $page->setCategory(Post::CATEGORY_PAGE);
            $page->setTitle(array_merge(BreadcrumbsGenerator::MENU, BreadcrumbsGenerator::OTHER_BREADCRUMBS)[$slug]);
            $page->setCreatedAt(new \DateTime());
            $page->setSlug($slug);
            $page->setStatus(Post::STATUS_ACTIVE);

            $manager->persist($page);
        }

        $form = $this->createForm(PagePostType::class, $page);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->flush();

            $this->addFlash('notice', 'Page modifiée');
        }

        return $this->render('relay/edit-page.html.twig', [
            'page' => $page,
            'editMode' => $mode,
            'form' => $form->createView(),
            'upload' => $router->generate('image_create'),
            'origin' => $this->getOrigin(),
        ]);
    }

}
