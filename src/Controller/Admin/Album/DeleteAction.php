<?php

namespace App\Controller\Admin\Album;

use App\Repository\AlbumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Suppression d'un album (POST + CSRF, admin uniquement).
 * Les médias associés sont supprimés en cascade.
 */
#[IsGranted('ROLE_ADMIN')]
class DeleteAction extends AbstractController
{
    #[Route(path: '/admin/album/delete/{id}', name: 'admin_album_delete', methods: ['POST'])]
    public function __invoke(Request $request, int $id, AlbumRepository $albumRepository, EntityManagerInterface $em)
    {
        if (!$this->isCsrfTokenValid('delete-album-' . $id, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $album = $albumRepository->find($id);
        $em->remove($album);
        $em->flush();

        $this->addFlash('success', 'Album supprimé avec succès.');

        return $this->redirectToRoute('admin_album_index');
    }
}
