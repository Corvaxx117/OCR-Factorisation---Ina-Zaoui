<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use App\Form\MediaType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Validation;

/**
 * Tests unitaires de l'entité Media : relations (User, Album) et accesseurs simples.
 */
class MediaTest extends TestCase
{
    private Media $media;
    private FormFactoryInterface $formFactory;

    // Exécuté avant CHAQUE test de cette classe : $media repart neuve à chaque fois, aucun état partagé entre tests.
    protected function setUp(): void
    {
        $this->media = new Media();
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory();
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

    public function testFormDefinesFileUploadConstraints(): void
    {
        $form = $this->formFactory->create(MediaType::class, new Media());
        $constraints = $form->get('file')->getConfig()->getOption('constraints');

        $this->assertCount(1, $constraints);
        $this->assertInstanceOf(File::class, $constraints[0]);
        $this->assertSame(2_000_000, $constraints[0]->maxSize);
        $this->assertSame(['image/jpeg', 'image/png', 'image/gif', 'image/webp'], $constraints[0]->mimeTypes);
    }
}
