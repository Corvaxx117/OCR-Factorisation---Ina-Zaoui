<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Centralise l'upload et la suppression physique des fichiers médias.
 */
class FileUploadService
{
    private const UPLOAD_DIRECTORY = 'uploads/';

    public function upload(UploadedFile $file): string
    {
        $filename = bin2hex(random_bytes(16)).'.'.$file->guessExtension();
        $file->move(self::UPLOAD_DIRECTORY, $filename);

        return self::UPLOAD_DIRECTORY.$filename;
    }

    public function remove(?string $path): void
    {
        if ($path && file_exists($path)) {
            unlink($path);
        }
    }
}
