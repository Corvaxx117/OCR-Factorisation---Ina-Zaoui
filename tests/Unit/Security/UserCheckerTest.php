<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * UserChecker est appelé par le firewall Symfony AVANT de valider le mot de passe.
 * On vérifie ici les 3 chemins possibles : compte actif, compte bloqué, type d'utilisateur non géré.
 */
class UserCheckerTest extends TestCase
{
    private UserChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new UserChecker();
    }

    public function testActiveUserIsAllowedToAuthenticate(): void
    {
        $user = new User();
        $user->setActive(true);

        // Aucune exception ne doit être levée : c'est le comportement attendu ici.
        $this->checker->checkPreAuth($user);
        $this->addToAssertionCount(1);
    }

    public function testInactiveUserIsBlockedWithExplicitMessage(): void
    {
        $user = new User();
        $user->setActive(false);

        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Votre compte a été désactivé.');

        $this->checker->checkPreAuth($user);
    }

    public function testNonAppUserIsIgnoredByChecker(): void
    {
        // UserChecker ne connaît que App\Entity\User : tout autre UserInterface doit être ignoré sans erreur.
        $otherUser = new class implements UserInterface {
            public function getRoles(): array { return []; }
            public function eraseCredentials(): void {}
            public function getUserIdentifier(): string { return 'other'; }
        };

        $this->checker->checkPreAuth($otherUser);
        $this->addToAssertionCount(1);
    }
}
