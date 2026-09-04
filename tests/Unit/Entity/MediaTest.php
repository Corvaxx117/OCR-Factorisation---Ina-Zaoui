<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests unitaires de l'entité Media : relations (User, Album) et accesseurs simples.
 */
class MediaTest extends TestCase
{
    private Media $media;
    private ValidatorInterface $validator;
    /** @var list<string> */
    private array $tmpFiles = [];

    // Exécuté avant CHAQUE test de cette classe : $media repart neuve à chaque fois, aucun état partagé entre tests.
    protected function setUp(): void
    {
        $this->media = new Media();
        // Validator construit directement depuis le composant, sans booter le kernel : toujours un test unitaire pur.
        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $path) {
            @unlink($path);
        }
    }

    public function testTitleAndPathAreStored(): void
    {
        $this->media->setTitle('Coucher de soleil');
        $this->media->setPath('/uploads/2024/sunset.jpg');

        $this->assertSame('Coucher de soleil', $this->media->getTitle());
        $this->assertSame('/uploads/2024/sunset.jpg', $this->media->getPath());
    }

    public function testMediaCanBeLinkedToUser(): void
    {
        $user = new User();

        $this->media->setUser($user);

        $this->assertSame($user, $this->media->getUser());
    }

    public function testMediaCanBeLinkedToAlbum(): void
    {
        $album = new Album();

        $this->media->setAlbum($album);

        $this->assertSame($album, $this->media->getAlbum());
    }

    public function testMediaWithoutAlbumReturnsNull(): void
    {
        $this->assertNull($this->media->getAlbum());
    }

    #[DataProvider('invalidFileProvider')]
    public function testInvalidFileIsRejectedByValidation(string $content, int $totalSize, string $originalName): void
    {
        $file = $this->createFakeUploadedFile($content, $totalSize, $originalName);

        $violations = $this->validator->validatePropertyValue(Media::class, 'file', $file);

        $this->assertGreaterThan(0, $violations->count(), 'Ce fichier aurait dû être rejeté par la contrainte Assert\File.');
    }

    /** @return iterable<string, array{string, int, string}> */
    public static function invalidFileProvider(): iterable
    {
        // Contenu texte brut : aucun magic number image, détecté comme text/plain par finfo.
        yield 'wrong mime type (text file renamed .jpg)' => ["Ceci n'est pas une image", 1024, 'fake.jpg'];

        // PNG minimal valide mais poids total > 2M une fois du bourrage ajouté (limite maxSize de la contrainte).
        yield 'oversized PNG (> 2M)' => [self::minimalPngContent(), 2 * 1024 * 1024 + 1, 'too-big.png'];
    }

    public function testValidFileIsAcceptedByValidation(): void
    {
        // PNG 1x1 minimal réel, sous la limite de taille : aucune violation attendue.
        $file = $this->createFakeUploadedFile(self::minimalPngContent(), 0, 'valid.png');

        $violations = $this->validator->validatePropertyValue(Media::class, 'file', $file);

        $this->assertCount(0, $violations);
    }

    // PNG 1x1 pixel transparent valide (nécessaire pour que finfo détecte réellement "image/png").
    private static function minimalPngContent(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }

    private function createFakeUploadedFile(string $content, int $totalSize, string $originalName): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'media_test_');

        if (false === $path) {
            throw new \RuntimeException('Impossible de créer le fichier temporaire.');
        }
        file_put_contents($path, $content.str_repeat("\0", max(0, $totalSize - strlen($content))));
        $this->tmpFiles[] = $path;

        // $test=true : bypasse la vérification is_uploaded_file() (impossible à satisfaire hors requête HTTP réelle).
        return new UploadedFile($path, $originalName, null, null, true);
    }
}
