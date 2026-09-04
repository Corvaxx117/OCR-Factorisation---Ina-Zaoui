<?php

namespace App\Controller\Admin\Media;

use App\Controller\Admin\AdminActionTrait;
use App\Entity\Media;
use App\Entity\User;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Suppression d'un média (POST + CSRF).
 * Vérifie les droits : admin ou propriétaire uniquement.
 */
class DeleteAction extends AbstractController
{
    use AdminActionTrait;

    #[Route(path: '/admin/media/delete/{id}', name: 'admin_media_delete', methods: ['POST'])]
    public function __invoke(Request $request, #[MapEntity(id: 'id')] Media $media, EntityManagerInterface $em, FileUploadService $fileUploadService, #[CurrentUser] User $currentUser): Response
    {
        $this->denyAccessUnlessValidCsrfToken('delete-media-'.$media->getId(), $request);

        if (!$this->isGranted('ROLE_ADMIN') && $media->getUser() !== $currentUser) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres médias.');
        }

        $em->remove($media);
        $em->flush();
        $fileUploadService->remove($media->getPath());

        return $this->redirectWithSuccess('Média supprimé avec succès.', 'admin_media_index');
    }
}
