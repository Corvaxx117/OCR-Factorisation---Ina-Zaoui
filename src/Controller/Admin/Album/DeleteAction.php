<?php

namespace App\Controller\Admin\Album;

use App\Controller\Admin\AdminActionTrait;
use App\Entity\Album;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
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
    use AdminActionTrait;

    #[Route(path: '/admin/album/delete/{id}', name: 'admin_album_delete', methods: ['POST'])]
    public function __invoke(
        Request $request,
        #[MapEntity(id: 'id')] Album $album,
        EntityManagerInterface $em,
    ) {
        $this->denyAccessUnlessValidCsrfToken('delete-album-'.$album->getId(), $request);

        $em->remove($album);
        $em->flush();

        return $this->redirectWithSuccess('Album supprimé avec succès.', 'admin_album_index');
    }
}
