<?php

namespace App\Tests\Functional\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * WebTestCase simule un vrai navigateur (client HTTP interne) sans réseau ni serveur réel :
 * on peut soumettre des formulaires, suivre des redirections, lire le contenu des pages.
 * Comme pour UserRepositoryTest, DAMA annule toute donnée persistée ici en fin de test.
 */
class LoginTest extends WebTestCase
{
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $hasher;

    protected function setUp(): void
    {
        // createClient() boot le kernel lui-même : ne jamais appeler bootKernel() en plus dans un WebTestCase.
        $this->client = static::createClient();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testLoginPageIsAccessible(): void
    {
        $this->client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
    }

    public function testAccessingAdminAreaWithoutLoginRedirectsToLogin(): void
    {
        $this->client->request('GET', '/admin/media');

        $this->assertResponseRedirects('/login');
    }

    public function testSuccessfulLoginRedirectsToDefaultTargetPath(): void
    {
        $this->createUser('guest-ok@test.local', 'correct-password', active: true);

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            '_username' => 'guest-ok@test.local',
            '_password' => 'correct-password',
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects('/admin/media');
    }

    public function testWrongPasswordIsRejected(): void
    {
        $this->createUser('guest-wrong-pwd@test.local', 'correct-password', active: true);

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            '_username' => 'guest-wrong-pwd@test.local',
            '_password' => 'totally-wrong-password',
        ]);
        $this->client->submit($form);

        // form_login redirige toujours vers la page de login en cas d'échec (jamais d'accès direct à /admin).
        $this->assertResponseRedirects('/login');
    }

    public function testInactiveUserIsBlockedAtLoginWithExplicitMessage(): void
    {
        $this->createUser('blocked@test.local', 'correct-password', active: false);

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            '_username' => 'blocked@test.local',
            '_password' => 'correct-password',
        ]);
        $this->client->submit($form);
        $this->client->followRedirect();

        // C'est UserChecker::checkPreAuth() qui déclenche ce message précis, pas une erreur générique de mot de passe.
        $this->assertSelectorTextContains('.alert-danger', 'Votre compte a été désactivé.');
    }

    private function createUser(string $email, string $plainPassword, bool $active): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setName($email);
        $user->setActive($active);
        $user->setPassword($this->hasher->hashPassword($user, $plainPassword));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
