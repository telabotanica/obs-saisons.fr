<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Observation;
use App\Entity\Species;
use App\Entity\Station;
use App\Service\EntityJsonSerialize;
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
    public function export(EntityManagerInterface $em)
    {
        $obs = $em->getRepository(Observation::class)->findAllPublic();

        return new JsonResponse($obs);
    }

    /**
     * @Route("/export/station/{slug}", name="export_station")
     */
    public function exportStation(EntityManagerInterface $em, string $slug)
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

        $serializer = new EntityJsonSerialize();

        return new Response(
            json_encode($serializer->jsonSerializeObservationForExport($data)),
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
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
        $pager->setMaxPerPage($request->query->get('size') ?? 2000);

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
    public function exportSpecies(EntityManagerInterface $em)
    {
        $data = $em->getRepository(Species::class)->findAllActiveArray();

        return new JsonResponse($data);
    }

    /**
     * @Route("/export/events", name="export_events")
     */
    public function exportEvents(EntityManagerInterface $em)
    {
        $data = $em->getRepository(Event::class)->findAllArray();

        return new JsonResponse($data);
    }

    /**
     * @Route("/export/events/{speciesId}", name="export_events_species")
     */
    public function exportEventSpecies(
        $speciesId,
        Request $request,
        EntityManagerInterface $em
    ) {
        $species = $em->getRepository(Species::class)->findOneById($speciesId);
        if (!$species) {
            throw new \InvalidArgumentException(sprintf('Invalid species with id %s', $speciesId));
        }

        $data = $em->getRepository(Event::class)->findBySpeciesArray($species);

        return new JsonResponse($data);
    }
}
