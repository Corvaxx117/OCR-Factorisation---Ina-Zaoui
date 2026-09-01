<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires purs : aucune base de données, aucun conteneur Symfony.
 * On instancie l'entité directement et on vérifie son comportement (getters/setters, logique de rôles).
 */
class UserTest extends TestCase
{
    private User $user;

    // Exécuté avant CHAQUE test : $user repart neuve, aucun état ne fuit d'un test à l'autre.
    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testUserIsActiveByDefault(): void
    {
        $this->assertTrue($this->user->isActive());
    }

    public function testSetActiveToFalseBlocksUser(): void
    {
        $this->user->setActive(false);

        $this->assertFalse($this->user->isActive());
    }

    #[DataProvider('adminFlagProvider')]
    public function testGetRolesDependsOnAdminFlag(bool $isAdmin, array $expectedRoles): void
    {
        $this->user->setAdmin($isAdmin);

        $this->assertSame($expectedRoles, $this->user->getRoles());
    }

    public static function adminFlagProvider(): iterable
    {
        yield 'guest has only ROLE_USER' => [false, ['ROLE_USER']];
        yield 'admin has ROLE_USER and ROLE_ADMIN' => [true, ['ROLE_USER', 'ROLE_ADMIN']];
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        $this->user->setEmail('guest@example.com');

        $this->assertSame('guest@example.com', $this->user->getUserIdentifier());
    }

    public function testNewUserHasEmptyMediaCollection(): void
    {
        $this->assertCount(0, $this->user->getMedias());
    }
}
