<?php

namespace App\Service;

use App\Service\SlugGenerator as SlugGenerator;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UploadService.
 */
class UploadService
{
    private $slugGenerator;
    const IMAGE_DIRECTORY_PATH = '../public/images';
    const IMAGE_URI_PREFIX = '/images';

    public function __construct()
    {
        $this->slugGenerator = new SlugGenerator();
    }

    public function uploadImage(?UploadedFile $formValue)
    {
        $fileName = null;
        if ($formValue) {
            if (!is_dir(self::IMAGE_DIRECTORY_PATH)) {
                mkdir(self::IMAGE_DIRECTORY_PATH, 0777, true);
            }

            $file = $formValue;
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugGenerator->slugify($originalFilename);
            $fileName = self::IMAGE_URI_PREFIX.'/'.$safeFilename.'-'.uniqid().'.'.$file->guessExtension();

            try {
                $file->move(
                    self::IMAGE_DIRECTORY_PATH, // Le dossier dans le quel le fichier va etre charger
                    $fileName
                );
            } catch (FileException $e) {
                return new Response($e->getMessage());
            }
        }

        return $fileName;
    }
}
