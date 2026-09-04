<?php

namespace App\Tests\Functional\Controller\Admin\Guest;

use App\DataFixtures\AppFixtures;
use App\Entity\Media;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Couvre les 4 actions Guest : Index, Add, Block, Delete.
 * Point d'attention particulier : le hash du mot de passe (via GuestRegistrationService)
 * et la suppression en cascade des médias (fichiers + BDD).
 */
class GuestCrudTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testIndexRequiresAdminRole(): void
    {
        $this->loginAs(admin: false);

        $this->client->request('GET', '/admin/guest');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testIndexListsOnlyNonAdminUsers(): void
    {
        $this->loginAs(admin: true);
        $this->createGuest('listed-guest@test.local');

        $this->client->request('GET', '/admin/guest');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'listed-guest@test.local');
        // L'admin connecté lui-même ne doit jamais apparaître dans la liste des invités.
        $this->assertSelectorTextNotContains('body', 'admin@test.local');
    }

    public function testAddGuestHashesPasswordAndSetsDefaultFlags(): void
    {
        $this->loginAs(admin: true);

        $crawler = $this->client->request('GET', '/admin/guest/add');
        $form = $crawler->filter('form')->form([
            'guest[name]' => 'Nouvel Invité',
            'guest[email]' => 'new-guest@test.local',
            'guest[password]' => 'plain-password-123',
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/guest');

        $this->em->clear();
        $guest = $this->em->getRepository(User::class)->findOneBy(['email' => 'new-guest@test.local']);
        $this->assertNotNull($guest);
        $this->assertNotSame('plain-password-123', $guest->getPassword(), 'Le mot de passe doit être haché, jamais stocké en clair.');
        $this->assertFalse($guest->isAdmin());
        $this->assertTrue($guest->isActive());
    }

    public function testBlockActionTogglesActiveStatus(): void
    {
        $this->loginAs(admin: true);
        $guest = $this->createGuest('to-block@test.local');

        $crawler = $this->client->request('GET', '/admin/guest');
        $token = $crawler->filter('form[action$="/admin/guest/block/'.$guest->getId().'"] input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/guest/block/'.$guest->getId(), ['_token' => $token]);

        $this->assertResponseRedirects('/admin/guest');
        $this->em->clear();
        $reloaded = $this->em->getRepository(User::class)->find($guest->getId());
        $this->assertFalse($reloaded->isActive());
    }

    public function testBlockedGuestCannotLogIn(): void
    {
        $this->loginAs(admin: true);
        $guest = $this->createGuest('blocked-then-login@test.local');

        $crawler = $this->client->request('GET', '/admin/guest');
        $token = $crawler->filter('form[action$="/admin/guest/block/'.$guest->getId().'"] input[name="_token"]')->attr('value');
        $this->client->request('POST', '/admin/guest/block/'.$guest->getId(), ['_token' => $token]);

        // Un seul client autorisé par test : on se déconnecte de la session admin avant de rejouer un login.
        $this->client->request('GET', '/logout');

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            '_username' => 'blocked-then-login@test.local',
            '_password' => 'password',
        ]);
        $this->client->submit($form);
        $this->client->followRedirect();

        $this->assertSelectorTextContains('.alert-danger', 'Votre compte a été désactivé.');
    }

    public function testDeleteGuestCascadesMediaDeletion(): void
    {
        $this->loginAs(admin: true);
        $guest = $this->createGuest('to-delete@test.local');
        $media = new Media();
        $media->setTitle('Photo à supprimer');
        $media->setPath('/uploads/nonexistent-test-file.jpg'); // inexistant : remove() ne doit pas planter dessus
        $media->setUser($guest);
        $this->em->persist($media);
        $this->em->flush();
        $mediaId = $media->getId();
        $guestId = $guest->getId();

        $crawler = $this->client->request('GET', '/admin/guest');
        $token = $crawler->filter('form[action$="/admin/guest/delete/'.$guestId.'"] input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/guest/delete/'.$guestId, ['_token' => $token]);

        $this->assertResponseRedirects('/admin/guest');
        $this->em->clear();
        $this->assertNull($this->em->getRepository(User::class)->find($guestId));
        $this->assertNull($this->em->getRepository(Media::class)->find($mediaId));
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

    private function loginAs(bool $admin): void
    {
        $email = $admin ? AppFixtures::ADMIN_EMAIL : AppFixtures::ACTIVE_GUEST_EMAIL;

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            '_username' => $email,
            '_password' => 'password',
        ]);
        $this->client->submit($form);
    }
}
