<?php

namespace App\Tests\Functional\Controller\Admin\Media;

use App\DataFixtures\AppFixtures;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Couvre Index (visibilité selon le rôle), Add (upload réel de fichier, validation MIME)
 * et Delete (propriétaire/admin uniquement) pour les médias.
 */
class MediaCrudTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    /** @var string[] chemins de fichiers réellement écrits sur disque par FileUploadService, à nettoyer */
    private array $uploadedFiles = [];

    protected function setUp(): void
    {
        $this->client = static::createClient();
        // Sans ça, chaque requête reboote le kernel et invalide $this->em, cassant le suivi des entités déjà persistées.
        $this->client->disableReboot();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
    }

    protected function tearDown(): void
    {
        // DAMA annule la BDD, mais pas les fichiers réellement déplacés sur disque par FileUploadService.
        foreach ($this->uploadedFiles as $path) {
            @unlink($path);
        }

        parent::tearDown(); // indispensable : arrête proprement le kernel avant le test suivant
    }

    public function testGuestOnlySeesOwnMedias(): void
    {
        $guest = $this->loginAs(admin: false);
        $otherGuest = $this->createGuest('other-owner@test.local');
        $this->createMediaFor($guest, 'Ma photo à moi');
        $this->createMediaFor($otherGuest, 'Photo de quelqu\'un d\'autre');

        $this->client->request('GET', '/admin/media');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Ma photo à moi');
        $this->assertSelectorTextNotContains('body', 'Photo de quelqu\'un d\'autre');
    }

    public function testAdminSeesAllMedias(): void
    {
        $this->loginAs(admin: true);
        $guest = $this->createGuest('any-owner@test.local');
        $this->createMediaFor($guest, 'Photo visible par admin');

        $this->client->request('GET', '/admin/media');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Photo visible par admin');
    }

    public function testAddMediaUploadsValidFileAndAssignsCurrentUser(): void
    {
        $guest = $this->loginAs(admin: false);

        $crawler = $this->client->request('GET', '/admin/media/add');
        $form = $crawler->filter('form')->form([
            'media[title]' => 'Photo valide',
        ]);
        $form['media[file]']->upload($this->createTestPngFile());
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/media');

        $this->em->clear();
        $media = $this->em->getRepository(Media::class)->findOneBy(['title' => 'Photo valide']);
        $this->assertNotNull($media);
        $this->assertSame($guest->getId(), $media->getUser()->getId());
        $this->uploadedFiles[] = $media->getPath();
        $this->assertFileExists($media->getPath());
    }

    public function testAddMediaRejectsInvalidMimeType(): void
    {
        $this->loginAs(admin: false);

        $crawler = $this->client->request('GET', '/admin/media/add');
        $form = $crawler->filter('form')->form([
            'media[title]' => 'Fichier invalide',
        ]);
        $form['media[file]']->upload($this->createTestTextFile());
        $this->client->submit($form);

        $this->assertResponseIsSuccessful(); // reste sur le formulaire, aucune redirection
        $this->em->clear();
        $this->assertNull($this->em->getRepository(Media::class)->findOneBy(['title' => 'Fichier invalide']));
    }

    public function testOwnerCanDeleteTheirOwnMedia(): void
    {
        $guest = $this->loginAs(admin: false);
        $media = $this->createMediaFor($guest, 'À supprimer par son propriétaire');

        $crawler = $this->client->request('GET', '/admin/media');
        $token = $crawler->filter('form[action$="/admin/media/delete/'.$media->getId().'"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/media/delete/'.$media->getId(), ['_token' => $token]);

        $this->assertResponseRedirects('/admin/media');
        $this->em->clear();
        $this->assertNull($this->em->getRepository(Media::class)->find($media->getId()));
    }

    public function testNonOwnerNonAdminCannotDeleteSomeoneElsesMedia(): void
    {
        $this->loginAs(admin: false);
        $otherGuest = $this->createGuest('victim-owner@test.local');
        $media = $this->createMediaFor($otherGuest, 'Pas à moi');

        // Symfony 8 valide le CSRF via l'origine de la requête (SameOriginCsrfTokenManager), pas la valeur
        // du token elle-même : on veut isoler ici la vérification de propriété, pas le CSRF.
        $this->client->request('POST', '/admin/media/delete/'.$media->getId(), ['_token' => 'csrf-token']);

        $this->assertResponseStatusCodeSame(403);
        $this->em->clear();
        $this->assertNotNull($this->em->getRepository(Media::class)->find($media->getId()));
    }

    public function testAdminCanDeleteAnyonesMedia(): void
    {
        $this->loginAs(admin: true);
        $guest = $this->createGuest('someone@test.local');
        $media = $this->createMediaFor($guest, 'Supprimable par admin');

        $crawler = $this->client->request('GET', '/admin/media');
        $token = $crawler->filter('form[action$="/admin/media/delete/'.$media->getId().'"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/media/delete/'.$media->getId(), ['_token' => $token]);

        $this->assertResponseRedirects('/admin/media');
        $this->em->clear();
        $this->assertNull($this->em->getRepository(Media::class)->find($media->getId()));
    }

    private function createMediaFor(User $user, string $title): Media
    {
        // Une requête HTTP entretemps réinitialise l'EntityManager : on refetch $user par id pour être sûr
        // qu'il est bien "managé" par l'EntityManager courant avant de le rattacher à un nouveau Media.
        $user = $this->em->getRepository(User::class)->find($user->getId());

        $media = new Media();
        $media->setTitle($title);
        $media->setPath('uploads/nonexistent-fixture-'.uniqid().'.jpg'); // fichier factice, jamais réellement écrit
        $media->setUser($user);
        $this->em->persist($media);
        $this->em->flush();

        return $media;
    }

    private function createTestPngFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'media_crud_test_').'.png';
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

        return $path;
    }

    private function createTestTextFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'media_crud_test_').'.txt';
        file_put_contents($path, 'Ceci n\'est pas une image.');

        return $path;
    }

    private function createGuest(string $email): User
    {
        $guest = new User();
        $guest->setEmail($email);
        $guest->setName($email);
        $guest->setActive(true);
        $guest->setAdmin(false);
        $guest->setPassword($this->hasher->hashPassword($guest, 'password'));
        $this->em->persist($guest);
        $this->em->flush();

        return $guest;
    }

    private function loginAs(bool $admin): User
    {
        $email = $admin ? AppFixtures::ADMIN_EMAIL : AppFixtures::ACTIVE_GUEST_EMAIL;
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            '_username' => $email,
            '_password' => 'password',
        ]);
        $this->client->submit($form);

        return $user;
    }
}
