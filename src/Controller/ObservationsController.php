<?php

namespace App\Controller;

use App\Repository\ObservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ObservationsController extends AbstractController
{
    /**
     * @Route("/observationsForCharts", name="observations_for_charts", methods={"POST"})
     */
    public function observationsForCharts(ObservationRepository $repo,Request $request)
    {
        $data = json_decode($request->getContent());
        $results = $repo->getObsForCharts($data);

        return new JsonResponse(
            [
                'results' => $results, 
            ]
           
       );
   
    }
}
