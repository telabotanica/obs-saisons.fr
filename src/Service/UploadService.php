<?php

namespace App\Service;

use App\Service\SlugGenerator as SlugGenerator;
use Exception;
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

    public function uploadFile(
        ?UploadedFile $formValue
    ): ?string {
        $fileDirectoryPath = $this->rootDir.self::IMAGE_DIRECTORY_PATH;
        $filePath = null;
        if ($formValue) {
            if (!is_dir($fileDirectoryPath)) {
                mkdir($fileDirectoryPath, 0777, true);
            }

            $file = $formValue;
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugGenerator->slugify($originalFilename).'-'.uniqid().'.'.$file->guessExtension();
            $filePath = self::IMAGE_URI_PREFIX.'/'.$safeFilename;

            try {
                $file->move(
                    $fileDirectoryPath, // Le dossier dans le quel le fichier va etre charger
                    $filePath
                );
            } catch (FileException $e) {
                return new Response($e->getMessage());
            }
        }

        return $filePath;
    }

    /**
     * @throws Exception
     */
    public function setFile(
        ?UploadedFile $uploadedFile,
        ?string $previousFilepath,
        bool $isDeleteFile
    ): ?string {
        $filePath = $previousFilepath;

        if ($uploadedFile || $isDeleteFile) {
            if ($previousFilepath) {
                // throws exception
                $this->deleteFile($previousFilepath);
            }
            $filePath = !$isDeleteFile ? $this->uploadFile($uploadedFile) : null;
        }

        return $filePath;
    }

    /**
     * @throws Exception
     */
    public function deleteFile(string $filePath): void
    {
        $absoluteFilePath = $this->rootDir.self::DIRECTORY_PATH.$filePath;
        $result = true;
        if (is_file($absoluteFilePath)) {
            $result = unlink($absoluteFilePath);
        }
        if (!$result) {
            throw new Exception(sprintf('Error deleting "%s"', $filePath));
        }
    }
}
