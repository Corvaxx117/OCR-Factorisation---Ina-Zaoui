<?php

namespace App\Controller\Admin\Album;

use App\Repository\AlbumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Suppression d'un album (POST + CSRF).
 * Les médias associés sont supprimés en cascade.
 */
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

        return $this->redirectToRoute('admin_album_index');
    }
}
