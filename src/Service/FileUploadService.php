<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Centralise l'upload et la suppression physique des fichiers médias.
 */
class FileUploadService
{
    private const PUBLIC_UPLOAD_PREFIX = 'uploads/';

    public function __construct(private readonly string $uploadDirectory)
    {
    }

    public function upload(UploadedFile $file): string
    {
        $filename = bin2hex(random_bytes(16)).'.'.$file->guessExtension();
        $file->move($this->uploadDirectory, $filename);

        return self::PUBLIC_UPLOAD_PREFIX.$filename;
    }

    public function remove(?string $path): void
    {
        if ($path && file_exists($this->getAbsolutePath($path))) {
            unlink($this->getAbsolutePath($path));
        }
    }

    private function getAbsolutePath(string $path): string
    {
        return $this->uploadDirectory.'/'.basename($path);
    }
}
