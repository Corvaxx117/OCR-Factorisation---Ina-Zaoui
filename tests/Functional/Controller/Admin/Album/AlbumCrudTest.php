<?php

namespace App\Tests\Functional\Controller\Admin\Album;

use App\DataFixtures\AppFixtures;
use App\Entity\Album;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Couvre les 4 actions Album : Index, Add, Update, Delete.
 * Vérifie aussi la faille corrigée précédemment (accès réservé à ROLE_ADMIN).
 */
class AlbumCrudTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testIndexRequiresAdminRole(): void
    {
        $this->loginAs(admin: false);

        $this->client->request('GET', '/admin/album');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testIndexDisplaysExistingAlbums(): void
    {
        $this->loginAs(admin: true);
        $this->createAlbum('Portfolio 2024');

        $this->client->request('GET', '/admin/album');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Portfolio 2024');
    }

    public function testAddAlbumPersistsAndRedirects(): void
    {
        $this->loginAs(admin: true);

        $crawler = $this->client->request('GET', '/admin/album/add');
        $form = $crawler->filter('form')->form([
            'album[name]' => 'Nouvel Album',
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/album');
        $album = $this->em->getRepository(Album::class)->findOneBy(['name' => 'Nouvel Album']);
        $this->assertNotNull($album);
    }

    public function testAddAlbumWithBlankNameShowsValidationError(): void
    {
        $this->loginAs(admin: true);

        $crawler = $this->client->request('GET', '/admin/album/add');
        $form = $crawler->filter('form')->form([
            'album[name]' => '',
        ]);
        $this->client->submit($form);

        $this->assertResponseIsSuccessful(); // reste sur le formulaire, pas de redirect
        $this->assertSelectorTextContains('.invalid-feedback, .form-error-message', 'obligatoire');
    }

    public function testUpdateAlbumChangesName(): void
    {
        $this->loginAs(admin: true);
        $album = $this->createAlbum('Nom original');

        $crawler = $this->client->request('GET', '/admin/album/update/'.$album->getId());
        $form = $crawler->filter('form')->form([
            'album[name]' => 'Nom modifié',
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/album');
        $this->em->refresh($album);
        $this->assertSame('Nom modifié', $album->getName());
    }

    public function testUpdateNonExistentAlbumReturns404(): void
    {
        $this->loginAs(admin: true);

        $this->client->request('GET', '/admin/album/update/999999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteAlbumWithValidCsrfTokenRemovesIt(): void
    {
        $this->loginAs(admin: true);
        $album = $this->createAlbum('À supprimer');
        $albumId = $album->getId();

        // Le token doit provenir du vrai générateur CSRF de Symfony, pas être inventé.
        $crawler = $this->client->request('GET', '/admin/album');
        $token = $crawler->filter('form[action$="/admin/album/delete/'.$albumId.'"] input[name="_token"]')->attr('value');

        $this->client->request('POST', '/admin/album/delete/'.$albumId, ['_token' => $token]);

        $this->assertResponseRedirects('/admin/album');
        // clear() force Doctrine à re-consulter la BDD au lieu de renvoyer $album depuis l'identity map.
        $this->em->clear();
        $this->assertNull($this->em->getRepository(Album::class)->find($albumId));
    }

    public function testDeleteAlbumWithInvalidCsrfTokenIsRejected(): void
    {
        $this->loginAs(admin: true);
        $album = $this->createAlbum('Protégé');

        $this->client->request('POST', '/admin/album/delete/'.$album->getId(), ['_token' => 'wrong-token']);

        $this->assertResponseStatusCodeSame(403);
        $this->assertNotNull($this->em->getRepository(Album::class)->find($album->getId()));
    }

    private function createAlbum(string $name): Album
    {
        $album = new Album();
        $album->setName($name);
        $this->em->persist($album);
        $this->em->flush();

        return $album;
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
