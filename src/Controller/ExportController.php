<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Observation;
use App\Entity\Species;
use App\Entity\Station;
use App\Entity\User;
use App\Security\Voter\UserVoter;
use App\Service\CsvService;
use App\Service\EntityJsonSerialize;
use App\Service\EntityRandomizer;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ExportController extends AbstractController
{
    /**
     * @Route("/export", name="export")
     */
    public function export(EntityManagerInterface $em, CsvService $csvService)
    {
        $obs = $em->getRepository(Observation::class)->findAllPublic();

        $response = $csvService->exportCsvAll($obs);

        // If csv fail, return a json file
        if ($response->getStatusCode() !== 200){
            return new JsonResponse($obs);
        }

        return $response;
    }

    /**
     * @Route("/export/station/{slug}", name="export_station")
     */
    public function exportStation(EntityManagerInterface $em, string $slug, CsvService $csvService)
    {
        $station = $em->getRepository(Station::class)
            ->findBy(['slug' => $slug])
        ;
        if (!$station) {
            throw new NotFoundHttpException(sprintf('Station slug %s not found', $slug));
        }

        $data = $em->getRepository(Observation::class)
            ->findByStationSlugForExport($slug)
        ;

        $response = $csvService->exportCsvStation($data, $slug);

        // If csv fail, return a json file
        if ($response->getStatusCode() !== 200){
            $serializer = new EntityJsonSerialize();

            return new Response(
                json_encode($serializer->jsonSerializeObservationForExport($data)),
                Response::HTTP_OK,
                ['content-type' => 'application/json']
            );
        }

        return $response;
    }

    /**
     * @Route("/export/filtered", name="export_filtered")
     */
    public function exportWithFilters(Request $request, EntityManagerInterface $em)
    {
        $qb = $em->getRepository(Observation::class)
            ->createFilteredObservationListQueryBuilder(
                $request->query->get('year'),
                $request->query->get('typeSpecies'),
                $request->query->get('species'),
                $request->query->get('event'),
                $request->query->get('department'),
                $request->query->get('region'),
                $request->query->get('station'),
                $request->query->get('individual'),
            );

        $serializer = new EntityJsonSerialize();

        $pager = new Pagerfanta(
            new QueryAdapter($qb)
        );

        $pager->setCurrentPage($request->query->get('page') ?? 1);
        $pager->setMaxPerPage($request->query->get('size') ?? 9000);

        $observations = iterator_to_array($pager->getCurrentPageResults());

        $url = $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo();
        $params = $request->query->all();
        if (array_key_exists('page', $params)) {
            unset($params['page']);
        }

        $ret = [
            'meta' => [
                'totalPages' => $pager->getNbPages(),
                'totalResults' => $pager->getNbResults(),
                'pageSize' => count($observations),
            ],
            'data' => $serializer->jsonSerializeObservationForExport($observations),
            'links' => [
                'self' => $request->getUri(),
                'first' => $url.'?'.http_build_query(array_merge($params, ['page' => 1])),
                'last' => $url.'?'.http_build_query(array_merge($params, ['page' => $pager->getNbPages()])),
                'prev' => $pager->hasPreviousPage() ? $url.'?'.http_build_query(array_merge($params, ['page' => $pager->getPreviousPage()])) : null,
                'next' => $pager->hasNextPage() ? $url.'?'.http_build_query(array_merge($params, ['page' => $pager->getNextPage()])) : null,
            ],
        ];

        return new Response(
            json_encode($ret),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }

    /**
     * @Route("/export/observation/events-evolution", name="events_evolution")
     */
    public function exportForEventsEvolutionChart(
        EntityManagerInterface $em,
        Request $request
    ) {
        $species = $request->query->get('species');
        $event = $request->query->get('event');
        if (!$species || !$event) {
            return new JsonResponse('Missing species or event param', 400);
        }

        $data = $em->getRepository(Observation::class)
            ->findFilteredForEventsEvolutionChart(
                $species,
                $event,
                $request->query->get('region'),
                $request->query->get('department')
            );

        return new JsonResponse($data);
    }

    /**
     * @Route("/export/species", name="export_species")
     */
    public function exportSpecies(EntityManagerInterface $em, CsvService $csvService)
    {
        $data = $em->getRepository(Species::class)->findAllActiveArray();

        $response = $csvService->exportCsvSpecies($data);

        // If csv fail, return a json file
        if ($response->getStatusCode() !== 200){
            return new JsonResponse($data);
        }

        return $response;
    }

    /**
     * @Route("/export/species/{speciesId}", name="export_single_species")
     */
    public function exportSingleSpecies($speciesId,EntityManagerInterface $em, CsvService $csvService)
    {
        $species = $em->getRepository(Species::class)->findBy(['id' => $speciesId]);

        if (!$species) {
            throw new \InvalidArgumentException(sprintf('Invalid species with id %s', $speciesId));
        }

        $data = $em->getRepository(Observation::class)->findBySpeciesForExport($speciesId);

        $speciesName = $species[0]->getVernacularName();
        $response = $csvService->exportCsvStation($data, 'observations_'.$speciesName);

        // If csv fail, return a json file
        if ($response->getStatusCode() !== 200){
            $serializer = new EntityJsonSerialize();

            return new Response(
                json_encode($serializer->jsonSerializeObservationForExport($data)),
                Response::HTTP_OK,
                ['content-type' => 'application/json']
            );
        }
        return $response;
    }

    /**
     * @Route("/export/events", name="export_events")
     */
    public function exportEvents(EntityManagerInterface $em, CsvService $csvService)
    {
        $data = $em->getRepository(Event::class)->findAllArray();

        $response = $csvService->exportCsvEvents($data, 'export_events');

        // If csv fail, return a json file
        if ($response->getStatusCode() !== 200){
            return new JsonResponse($data);
        }

        return $response;
    }

    /**
     * @Route("/export/events/{speciesId}", name="export_events_species")
     */
    public function exportEventSpecies(
        $speciesId,
        Request $request,
        EntityManagerInterface $em,
        CsvService $csvService
    ) {
        $species = $em->getRepository(Species::class)->findOneById($speciesId);
        if (!$species) {
            throw new \InvalidArgumentException(sprintf('Invalid species with id %s', $speciesId));
        }

        $data = $em->getRepository(Event::class)->findBySpeciesArray($species);

        $speciesName = $species->getVernacularName();
        $fileName = 'export_events_'.str_replace(' ', '-', $speciesName);

        $response = $csvService->exportCsvEvents($data, $fileName);

        // If csv fail, return a json file
        if ($response->getStatusCode() !== 200){
            return new JsonResponse($data);
        }

        return $response;
    }
	
	/**
	 * @Route("/export/user/{userId}", name="export_user")
	 */
	public function exportUserObs($userId, EntityManagerInterface $em, CsvService $csvService)
	{
		$this->denyAccessUnlessGranted(UserVoter::LOGGED);

		$user = $em->getRepository(User::class)
			->find($userId);
		
		if (!$user) {
			throw $this->createNotFoundException('L’utilisateur n’existe pas');
		}
		
		$userName = $user->getDisplayName();
		$data = $em->getRepository(Observation::class)->findOrderedObsPerUserForExport($user);
		$response = $csvService->exportCsvStation($data, $userName);
		
		// If csv fail, return a json file
		if ($response->getStatusCode() !== 200){
			$serializer = new EntityJsonSerialize();
			
			return new Response(
				json_encode($serializer->jsonSerializeObservationForExport($data)),
				Response::HTTP_OK,
				['content-type' => 'application/json']
			);
		}
		
		return $response;
	}

    /**
     * @Route("/export/observation/calender-data", name="calender_data")
     */
    public function exportForCalenderData(
        EntityManagerInterface $em,
        Request $request){
        
        $serializer = new EntityJsonSerialize();

        $species = $em->getRepository(Species::class)->findAllActive();
        
        if (empty($request->query->get('species'))) {
            $data = $em->getRepository(Observation::class)->findAllPublic();
        } else {

            //Get query parameters
            $selectedSpeciesIds = $request->query->get('species', (new \App\Service\EntityRandomizer)->getRandomSpecies($species, 2));
            $selectedEventId = $request->query->get('event', []);
            $selectedYear = $request->query->get('year', [1]);
    
            
    
            //Error handling
            if(!$selectedSpeciesIds){
                return new JsonResponse('Missing species param', 400);
            }
    
            //Get data
            $data = $em->getRepository(Observation::class)
                ->findObservationsGraph(
                    $selectedSpeciesIds,
                    $selectedEventId,
                    $selectedYear
                );
        }
        $serializedData = $serializer->serializeJsonForCalendar($data);
        return new Response(
            $serializedData,
            Response::HTTP_OK,
            ['content-type' => 'application/json']
            );
    }
}
