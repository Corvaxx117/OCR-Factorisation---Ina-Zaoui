<?php

namespace App\Controller\Admin\Album;

use App\Controller\Admin\AdminActionTrait;
use App\Entity\Album;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Suppression d'un album (POST + CSRF, admin uniquement).
 * Les médias associés sont supprimés en cascade, fichiers sur disque compris.
 */
#[IsGranted('ROLE_ADMIN')]
class DeleteAction extends AbstractController
{
    use AdminActionTrait;

    #[Route(path: '/admin/album/delete/{id}', name: 'admin_album_delete', methods: ['POST'])]
    public function __invoke(
        Request $request,
        #[MapEntity(id: 'id')] Album $album,
        EntityManagerInterface $em,
        FileUploadService $fileUploadService,
    ): Response {
        $this->denyAccessUnlessValidCsrfToken('delete-album-'.$album->getId(), $request);

        // La cascade Doctrine retire les lignes en base mais laisserait les fichiers sur le disque.
        foreach ($album->getMedias() as $media) {
            $fileUploadService->remove($media->getPath());
        }

        $em->remove($album);
        $em->flush();

        return $this->redirectWithSuccess('Album supprimé avec succès.', 'admin_album_index');
    }
}
