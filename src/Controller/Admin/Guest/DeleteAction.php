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
 * Suppression d'un invité et de tous ses médias (fichiers + BDD).
 * Protégé par CSRF, réservé à l'admin.
 */
#[IsGranted('ROLE_ADMIN')]
class DeleteAction extends AbstractController
{
    #[Route(path: '/admin/guest/delete/{id}', name: 'admin_guest_delete', methods: ['POST'])]
    public function __invoke(Request $request, int $id, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete-guest-' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $guest = $userRepository->find($id);

        if (!$guest) {
            throw $this->createNotFoundException('Invité introuvable.');
        }

        // Suppression des fichiers physiques des médias
        foreach ($guest->getMedias() as $media) {
            $filePath = $media->getPath();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            $em->remove($media);
        }

        $em->remove($guest);
        $em->flush();

        $this->addFlash('success', 'Invité supprimé avec succès.');

        return $this->redirectToRoute('admin_guest_index');
    }
}
