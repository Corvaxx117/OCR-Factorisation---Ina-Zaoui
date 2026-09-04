<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Front;

use App\DataFixtures\AppFixtures;
use App\Entity\Album;
use App\Entity\User;
use App\Tests\Support\DoctrineTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Vérifie les parcours accessibles aux visiteurs du site à partir des données fixtures.
 */
class FrontOfficeTest extends WebTestCase
{
    use DoctrineTestTrait;

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->entityManager();
    }

    #[DataProvider('publicPageProvider')]
    public function testPublicStaticPagesAreAccessible(string $path, string $expectedContent): void
    {
        $this->client->request('GET', $path);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $expectedContent);
    }

    /** @return iterable<string, array{string, string}> */
    public static function publicPageProvider(): iterable
    {
        yield 'home page' => ['/', 'Photographe'];
        yield 'about page' => ['/about', 'Qui suis-je'];
    }

    public function testGuestsPageDisplaysOnlyActiveGuests(): void
    {
        $this->client->request('GET', '/guests');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.guests', 'Invité actif');
        $this->assertSelectorTextNotContains('.guests', 'Invité bloqué');
        $this->assertSelectorTextNotContains('.guests', 'Administratrice');
    }

    public function testActiveGuestProfileIsAccessible(): void
    {
        $guest = $this->findUserByEmail(AppFixtures::ACTIVE_GUEST_EMAIL);

        $this->client->request('GET', '/guest/'.$guest->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Invité actif');
        $this->assertSelectorExists('img[alt="Photo invité actif"]');
    }

    public function testBlockedGuestProfileReturns404(): void
    {
        $guest = $this->findUserByEmail(AppFixtures::BLOCKED_GUEST_EMAIL);

        $this->client->request('GET', '/guest/'.$guest->getId());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAdminProfileCannotBeExposedAsGuestProfile(): void
    {
        $admin = $this->findUserByEmail(AppFixtures::ADMIN_EMAIL);

        $this->client->request('GET', '/guest/'.$admin->getId());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testDefaultPortfolioDisplaysOnlyAdminMedias(): void
    {
        $this->client->request('GET', '/portfolio');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('img[alt="Photo admin"]');
        $this->assertSelectorNotExists('img[alt="Photo invité actif"]');
    }

    public function testPortfolioCanBeFilteredByAlbum(): void
    {
        $album = $this->em->getRepository(Album::class)->findOneBy(['name' => 'Portfolio de test']);
        $this->assertNotNull($album);

        $this->client->request('GET', '/portfolio/'.$album->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('img[alt="Photo admin"]');
        $this->assertSelectorExists('img[alt="Photo invité actif"]');
    }

    private function findUserByEmail(string $email): User
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertInstanceOf(User::class, $user);

        return $user;
    }
}
