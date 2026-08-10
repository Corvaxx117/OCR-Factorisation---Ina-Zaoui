<?php

namespace App\Controller\Admin\Media;

use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Suppression d'un média (POST + CSRF).
 * Vérifie les droits : admin ou propriétaire uniquement.
 */
class DeleteAction extends AbstractController
{
    #[Route(path: '/admin/media/delete/{id}', name: 'admin_media_delete', methods: ['POST'])]
    public function __invoke(Request $request, int $id, MediaRepository $mediaRepository, EntityManagerInterface $em)
    {
        if (!$this->isCsrfTokenValid('delete-media-' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $media = $mediaRepository->find($id);

        if (!$media) {
            throw $this->createNotFoundException('Média introuvable.');
        }

        if (!$this->isGranted('ROLE_ADMIN') && $media->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres médias.');
        }

        $em->remove($media);
        $em->flush();

        if (file_exists($media->getPath())) {
            unlink($media->getPath());
        }

        $this->addFlash('success', 'Média supprimé avec succès.');

        return $this->redirectToRoute('admin_media_index');
    }
}
