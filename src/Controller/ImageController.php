<?php

namespace App\Controller;

use App\Security\Voter\UserVoter;
use App\Service\UploadService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ImageController extends AbstractController
{
    /**
     * @Route("/image/new", name="image_create")
     */
    public function imageNew(Request $request, UploadService $uploadService)
    {
        $this->denyAccessUnlessGranted(UserVoter::LOGGED);

        if (!$this->getUser()->isAdmin()) {
            throw new AccessDeniedException('You shall not pass');
        }

        $path = $uploadService->uploadFile(
            $request->files->get('upload')
        );

        return new JsonResponse(['url' => $path]);
    }

    /**
     * @Route("/images", name="image_list")
     */
    public function imagesList()
    {
    }

    /**
     * @Route("/image/delete", name="image_delete")
     */
    public function imageDelete()
    {
    }
}
