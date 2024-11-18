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
use App\Entity\FrenchRegions;
use App\Form\GlobalStatsTypeOcc;
use App\Form\GlobalStatsTypePaca;

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
     * @Route("/relay/global-stats/{mode}", name="relay_global_stats")
     */
    public function getGlobalStats(Stats $statsService,Request $request,int $mode=null){
        $this->denyAccessUnlessGranted('ROLE_RELAY');

        if (empty($mode)){
            $dptsOcc = $this->getDptIndexed(12);
        
            $dptsPaca = $this->getDptIndexed(13);
            $departments=[$dptsOcc[0],$dptsPaca[0]];
            
            $formOcc = $this->createForm(GlobalStatsTypeOcc::class,$departments, ['departmentsOcc'=>$dptsOcc[0]]);
            $formOcc->handleRequest($request);
            
            if ($formOcc->isSubmitted() && $formOcc->isValid()){
                $departmentOcc = $formOcc->get('departmentsOcc')->getData();
                
            }else{
                $departmentOcc=$dptsOcc[0]['09'];
            }
            
            $formPaca = $this->createForm(GlobalStatsTypePaca::class,$departments, ['departmentsPaca'=>$dptsPaca[0]]);
            $formPaca->handleRequest($request);
            
            if ($formPaca->isSubmitted() && $formPaca->isValid()){
                $departmentPaca = $formPaca->get('departmentsPaca')->getData();
                
            }else{

                $departmentPaca=$dptsPaca[0]['04'];
            }
            // Indicateurs
            $stats = $statsService->getGlobalStats($departmentOcc,$departmentPaca);
            
            return $this->render('relay/global-stats.html.twig', [
                'stats' => $stats,
                'departmentsOcc' => $dptsOcc[1],
                'departmentsPaca' => $dptsPaca[1],
                'formOcc' => $formOcc->createView(),
                'formPaca' => $formPaca->createView()
            ]);
        }else if($mode===1){
            $dptsOcc = $this->getDptIndexed(12);
        
            $dptsPaca = $this->getDptIndexed(13);
            $departments=[$dptsOcc[0],$dptsPaca[0]];
            
            $formOcc = $this->createForm(GlobalStatsTypeOcc::class,$departments, ['departmentsOcc'=>$dptsOcc[0]]);
            $formOcc->handleRequest($request);
            
            if ($formOcc->isSubmitted() && $formOcc->isValid()){
                $departmentOcc = $formOcc->get('departmentsOcc')->getData();
                
            }else{
                $departmentOcc=$dptsOcc[0]['09'];
                
            }
            
            // Indicateurs
            $stats = array_merge($statsService->getGlobalStatsOccitanie($departmentOcc),$statsService->getGeneralStats());
            
            return $this->render('relay/global-stats.html.twig', [
                'stats' => $stats,
                'departmentsOcc' => $dptsOcc[1],
                'formOcc' => $formOcc->createView()
            ]);
        }else if($mode===2){
            $dptsOcc = $this->getDptIndexed(12);
        
            $dptsPaca = $this->getDptIndexed(13);
            $departments=[$dptsOcc[0],$dptsPaca[0]];
            
            $formPaca = $this->createForm(GlobalStatsTypePaca::class,$departments, ['departmentsPaca'=>$dptsPaca[0]]);
            $formPaca->handleRequest($request);
            
            if ($formPaca->isSubmitted() && $formPaca->isValid()){
                $departmentPaca = $formPaca->get('departmentsPaca')->getData();
                
            }else{
                $departmentPaca=$dptsPaca[0]['04'];
                
            }
            
            // Indicateurs
            $stats = array_merge($statsService->getGlobalStatsProvence($departmentPaca),$statsService->getGeneralStats());
            
            return $this->render('relay/global-stats.html.twig', [
                'stats' => $stats,
                'departmentsPaca' => $dptsPaca[1],
                'formPaca' => $formPaca->createView()
            ]);
        }
        
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

    public function getDptIndexed($region){
        $departments = FrenchRegions::getDepartmentsIdsByRegionId($region);
        $dptIndexed = [];
        foreach ($departments as $dpt){
            $dptIndexed[$dpt] = $dpt;
        }
        return [$dptIndexed,$departments];
    }

}
