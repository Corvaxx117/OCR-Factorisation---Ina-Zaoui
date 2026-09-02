<?php

namespace App\Controller\Admin\Album;

use App\Entity\Album;
use App\Form\AlbumType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Formulaire de modification d'un album existant (admin uniquement).
 */
#[IsGranted('ROLE_ADMIN')]
class UpdateAction extends AbstractController
{
    #[Route(path: '/admin/album/update/{id}', name: 'admin_album_update')]
    public function __invoke(Request $request, #[MapEntity(id: 'id')] Album $album, EntityManagerInterface $em)
    {
        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Album modifié avec succès.');

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/update.html.twig', ['form' => $form->createView()]);
    }
}
