<?php

namespace App\DataFixtures;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[When(env: 'test')]
class AppFixtures extends Fixture
{
    public const ADMIN_REFERENCE = 'admin';
    public const ACTIVE_GUEST_REFERENCE = 'active-guest';
    public const BLOCKED_GUEST_REFERENCE = 'blocked-guest';
    public const PORTFOLIO_ALBUM_REFERENCE = 'portfolio-album';
    public const EMPTY_ALBUM_REFERENCE = 'empty-album';
    public const ADMIN_EMAIL = 'admin@test.local';
    public const ACTIVE_GUEST_EMAIL = 'guest-active@test.local';
    public const BLOCKED_GUEST_EMAIL = 'guest-blocked@test.local';

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->createUser(self::ADMIN_EMAIL, 'Administratrice', true, true);
        $activeGuest = $this->createUser(self::ACTIVE_GUEST_EMAIL, 'Invité actif', false, true);
        $blockedGuest = $this->createUser(self::BLOCKED_GUEST_EMAIL, 'Invité bloqué', false, false);

        $portfolioAlbum = $this->createAlbum('Portfolio de test');
        $emptyAlbum = $this->createAlbum('Album vide de test');

        $manager->persist($admin);
        $manager->persist($activeGuest);
        $manager->persist($blockedGuest);
        $manager->persist($portfolioAlbum);
        $manager->persist($emptyAlbum);

        $manager->persist($this->createMedia('Photo admin', 'uploads/fixtures/admin.jpg', $admin, $portfolioAlbum));
        $manager->persist($this->createMedia('Photo invité actif', 'uploads/fixtures/guest-active.jpg', $activeGuest, $portfolioAlbum));

        $this->addReference(self::ADMIN_REFERENCE, $admin);
        $this->addReference(self::ACTIVE_GUEST_REFERENCE, $activeGuest);
        $this->addReference(self::BLOCKED_GUEST_REFERENCE, $blockedGuest);
        $this->addReference(self::PORTFOLIO_ALBUM_REFERENCE, $portfolioAlbum);
        $this->addReference(self::EMPTY_ALBUM_REFERENCE, $emptyAlbum);

        $manager->flush();
    }

    private function createUser(string $email, string $name, bool $admin, bool $active): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setAdmin($admin);
        $user->setActive($active);
        $user->setPassword($this->hasher->hashPassword($user, 'password'));

        return $user;
    }

    private function createAlbum(string $name): Album
    {
        $album = new Album();
        $album->setName($name);

        return $album;
    }

    private function createMedia(string $title, string $path, User $user, Album $album): Media
    {
        $media = new Media();
        $media->setTitle($title);
        $media->setPath($path);
        $media->setUser($user);
        $media->setAlbum($album);

        return $media;
    }
}
