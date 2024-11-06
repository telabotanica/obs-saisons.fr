<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Stats;
use App\Entity\User;

class RelayController extends AbstractController
{
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

}
