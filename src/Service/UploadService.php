<?php

namespace App\Service;

use App\Service\SlugGenerator as SlugGenerator;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class UploadService.
 */
class UploadService
{
    private $slugGenerator;
    private $rootDir;
    const DIRECTORY_PATH = '/public';
    const IMAGE_DIRECTORY_PATH = '/public/images';
    const IMAGE_URI_PREFIX = '/images';

    public function __construct(KernelInterface $kernel)
    {
        $this->rootDir = $kernel->getProjectDir();
        $this->slugGenerator = new SlugGenerator();
    }

    public function uploadImage(?UploadedFile $formValue)
    {
        $imageDirectoryPath = $this->rootDir.self::IMAGE_DIRECTORY_PATH;
        $fileName = null;
        if ($formValue) {
            if (!is_dir($imageDirectoryPath)) {
                mkdir($imageDirectoryPath, 0777, true);
            }

            $file = $formValue;
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugGenerator->slugify($originalFilename).'-'.uniqid().'.'.$file->guessExtension();
            $fileName = self::IMAGE_URI_PREFIX.'/'.$safeFilename;

            try {
                $file->move(
                    $imageDirectoryPath, // Le dossier dans le quel le fichier va etre charger
                    $fileName
                );
            } catch (FileException $e) {
                return new Response($e->getMessage());
            }
        }

        return $fileName;
    }

    public function deleteImage(string $imagePath): void
    {
        $directoryPath = $this->rootDir.self::DIRECTORY_PATH;
        $result = unlink($directoryPath.$imagePath);
        if (!$result) {
            throw new \Exception(sprintf('Error deleting "%s"', $imagePath));
        }
    }
}
