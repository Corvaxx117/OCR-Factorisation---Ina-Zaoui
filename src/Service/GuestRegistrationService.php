<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Finalise la création d'un compte invité : hash du mot de passe, rôle et statut par défaut, persistance.
 * Extrait du controller pour rester testable sans requête HTTP.
 */
class GuestRegistrationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    public function register(User $guest, string $plainPassword): void
    {
        $guest->setPassword($this->hasher->hashPassword($guest, $plainPassword));
        $guest->setAdmin(false);
        $guest->setActive(true);

        $this->em->persist($guest);
        $this->em->flush();
    }
}
