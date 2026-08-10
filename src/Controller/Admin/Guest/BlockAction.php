<?php

namespace App\Controller\Admin\Guest;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    #[Route(path: '/admin/guest/block/{id}', name: 'admin_guest_block', methods: ['POST'])]
    public function __invoke(Request $request, int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('block-guest-' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $guest = $userRepository->find($id);

        if (!$guest) {
            throw $this->createNotFoundException('Invité introuvable.');
        }

        $guest->setActive(!$guest->isActive());
        $em->flush();

        $this->addFlash('success', $guest->isActive() ? 'Invité débloqué.' : 'Invité bloqué.');

        return $this->redirectToRoute('admin_guest_index');
    }
}
