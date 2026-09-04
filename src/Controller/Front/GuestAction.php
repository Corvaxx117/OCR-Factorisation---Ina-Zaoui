<?php

namespace App\Controller\Front;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Affiche le profil et les médias d'un invité.
 * Retourne 404 si l'invité est inactif ou inexistant.
 */
class GuestAction extends AbstractController
{
    #[Route(path: '/guest/{id}', name: 'guest')]
    public function __invoke(#[MapEntity(id: 'id')] User $guest)
    {
        if ($guest->isAdmin() || !$guest->isActive()) {
            throw $this->createNotFoundException('Invité introuvable.');
        }

        return $this->render('front/guest.html.twig', [
            'guest' => $guest
        ]);
    }
}
