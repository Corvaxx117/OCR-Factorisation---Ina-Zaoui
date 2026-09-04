<?php

namespace App\Controller\Admin\Guest;

use App\Controller\Admin\AdminActionTrait;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Toggle le statut actif/inactif d'un invité (POST + CSRF).
 * Un invité bloqué ne peut plus se connecter ni apparaître sur le site.
 */
#[IsGranted('ROLE_ADMIN')]
class BlockAction extends AbstractController
{
    use AdminActionTrait;

    #[Route(path: '/admin/guest/block/{id}', name: 'admin_guest_block', methods: ['POST'])]
    public function __invoke(
        Request $request,
        #[MapEntity(id: 'id')] User $guest,
        EntityManagerInterface $em,
    ): Response {
        $this->denyAccessUnlessValidCsrfToken('block-guest-'.$guest->getId(), $request);

        $guest->setActive(!$guest->isActive());
        $em->flush();

        return $this->redirectWithSuccess($guest->isActive() ? 'Invité débloqué.' : 'Invité bloqué.', 'admin_guest_index');
    }
}
