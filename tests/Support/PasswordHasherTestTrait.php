<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait PasswordHasherTestTrait
{
    abstract protected static function getContainer(): Container;

    protected function passwordHasher(): UserPasswordHasherInterface
    {
        return static::getContainer()->get(UserPasswordHasherInterface::class);
    }
}
