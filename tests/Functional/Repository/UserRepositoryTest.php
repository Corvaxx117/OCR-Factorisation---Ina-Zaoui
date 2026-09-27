<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Media;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Support\DoctrineTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * KernelTestCase démarre le kernel Symfony (conteneur, config, connexion BDD) sans lancer de serveur HTTP.
 * On l'utilise ici pour tester une vraie requête Doctrine contre la base de test "ina_zaoui_test".
 *
 * Grâce à DAMADoctrineTestBundle, tout ce qui est persisté ici est fait dans une transaction
 * automatiquement annulée à la fin du test : aucun nettoyage manuel n'est nécessaire.
 */
class UserRepositoryTest extends KernelTestCase
{
    use DoctrineTestTrait;

    private EntityManagerInterface $em;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = $this->entityManager();
        $this->userRepository = $this->em->getRepository(User::class);
    }

    public function testFindActiveGuestsWithMediasExcludesInactiveAndAdmin(): void
    {
        $activeGuest = $this->createUser('active-guest@test.local', admin: false, active: true);
        $this->createUser('blocked-guest@test.local', admin: false, active: false);
        $this->createUser('repository-admin@test.local', admin: true, active: true);

        $media = new Media();
        $media->setTitle('Photo de test');
        $media->setPath('/uploads/test.jpg');
        $media->setUser($activeGuest);
        $this->em->persist($media);
        $this->em->flush();

        $result = $this->userRepository->findActiveGuestsWithMedias();

        $emails = array_map(static fn (User $u) => $u->getEmail(), $result);

        $this->assertContains('active-guest@test.local', $emails);
        $this->assertNotContains('blocked-guest@test.local', $emails, 'Un invité bloqué ne doit jamais apparaître ici.');
        $this->assertNotContains('repository-admin@test.local', $emails, 'Un admin n\'est pas un invité.');
    }

    public function testFindActiveGuestsWithMediasPreloadsMediaCollection(): void
    {
        $guest = $this->createUser('guest-with-media@test.local', admin: false, active: true);

        $media = new Media();
        $media->setTitle('Portrait');
        $media->setPath('/uploads/portrait.jpg');
        $media->setUser($guest);
        $this->em->persist($media);
        $this->em->flush();
        $this->em->clear(); // on vide l'identity map pour forcer une vraie relecture depuis la BDD

        $result = $this->userRepository->findActiveGuestsWithMedias();
        $reloadedGuest = current(array_filter($result, static fn (User $u) => 'guest-with-media@test.local' === $u->getEmail()));

        $this->assertNotFalse($reloadedGuest);
        // Le LEFT JOIN + addSelect('m') doit avoir chargé les médias en même temps que l'utilisateur (pas de requête supplémentaire).
        $this->assertCount(1, $reloadedGuest->getMedias());
    }

    private function createUser(string $email, bool $admin, bool $active): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName($email);
        $user->setPassword('not-used-in-this-test');
        $user->setAdmin($admin);
        $user->setActive($active);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
