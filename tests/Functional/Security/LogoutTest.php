<?php

namespace App\Tests\Functional\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LogoutTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testLogoutRevokesAccessToAdminArea(): void
    {
        $this->loginAsNewGuest();

        // Avant logout : l'invité connecté peut accéder à /admin/media.
        $this->client->request('GET', '/admin/media');
        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/logout');
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Après logout : la session est bien invalidée, retour au comportement "non connecté".
        $this->client->request('GET', '/admin/media');
        $this->assertResponseRedirects('/login');
    }

    private function loginAsNewGuest(): void
    {
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setEmail('logout-test@test.local');
        $user->setName('logout-test@test.local');
        $user->setActive(true);
        $user->setPassword($hasher->hashPassword($user, 'correct-password'));
        $this->em->persist($user);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->filter('form')->form([
            '_username' => 'logout-test@test.local',
            '_password' => 'correct-password',
        ]);
        $this->client->submit($form);
    }
}
