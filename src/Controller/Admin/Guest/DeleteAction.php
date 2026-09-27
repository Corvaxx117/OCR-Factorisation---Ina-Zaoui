<?php

namespace App\Controller\Admin\Guest;

use App\Controller\Admin\AdminActionTrait;
use App\Entity\User;
use App\Service\FileUploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
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
    use AdminActionTrait;

    #[Route(path: '/admin/guest/delete/{id}', name: 'admin_guest_delete', methods: ['POST'])]
    public function __invoke(Request $request, #[MapEntity(id: 'id')] User $guest, EntityManagerInterface $em, FileUploadService $fileUploadService): Response
    {
        $this->denyAccessUnlessValidCsrfToken('delete-guest-'.$guest->getId(), $request);

        foreach ($guest->getMedias() as $media) {
            $fileUploadService->remove($media->getPath());
            $em->remove($media);
        }

        $em->remove($guest);
        $em->flush();

        return $this->redirectWithSuccess('Invité supprimé avec succès.', 'admin_guest_index');
    }
}
